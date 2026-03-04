    # Phase 12: Admin Dashboard

## Context

heyBertie needs an internal operations dashboard — not just a stats viewer, but the tool we use when a business calls asking for help setting up, a customer emails about a booking issue, or we need to change someone's plan. It's our CRM with the ability to action changes on behalf of users. The existing codebase already has the auth primitives in place: `User.super` boolean flag, `EnsureSuperAdmin` middleware, business verification status/documents with reviewer tracking, and subscription tier/status models. The business dashboard pattern (Inertia + React with sidebar layout) provides the architectural template.

This phase also includes a business-facing settings page (auto-confirm toggle), which was deferred from Phase 7b and listed under the original Phase 10 in the roadmap.

## Current State

- **Auth**: `User.super` boolean exists, `EnsureSuperAdmin` middleware exists (used for Statamic /docs protection)
- **Business verification**: `verification_status` (pending/verified/rejected), `VerificationDocument` model with review workflow (reviewed_by_user_id, reviewer_notes, reviewed_at)
- **Subscription system**: Three tiers (Free/Solo/Salon), five statuses (trial/active/past_due/cancelled/suspended), Stripe Cashier integration
- **Business dashboard**: Inertia + React at `/{handle}/dashboard`, sidebar layout, `ResolveManagedBusiness` middleware
- **No admin routes exist yet** — only Statamic CP at `/cp` and the super_admin Statamic protect driver
- **Business settings**: JSON `settings` column exists, `auto_confirm_bookings` key is read by `BookingService` but has no UI to toggle it

## Operational Features Summary

When someone contacts us, the admin dashboard is the single place we go to resolve it. Here's what we need to be able to do:

### Business support scenarios
- **"Help me set up"** — View their onboarding progress, see what step they're stuck on, impersonate to complete steps on their behalf
- **"I want to change plan"** — View current tier/status, change subscription tier, extend or grant a trial
- **"My listing isn't showing"** — Check verification status, review/approve documents, check `is_active` flag
- **"I need to change my handle"** — Change handle on their behalf (bypassing 30-day cooldown if needed)
- **"Deposits aren't working"** — Check Stripe Connect onboarding status, view deposit settings, check `stripe_connect_id`
- **"I want to close my account"** — Suspend business, cancel subscription

### Customer support scenarios
- **"I can't find my booking"** — Search bookings by reference, email, or phone; view full booking detail
- **"I need to cancel/reschedule"** — Cancel with refund or reschedule to new slot on their behalf
- **"I didn't get my confirmation email"** — Check email log for the booking, see delivery status
- **"I'm being charged but didn't book"** — View transaction history, issue refund

### Platform operations
- **Verification queue** — Review pending businesses, approve/reject with notes
- **MRR & churn tracking** — Subscription breakdown by tier, trial conversion rate
- **Flagged reviews** — Moderate reviews marked as inappropriate

### Activity timeline (on every user & business profile)
A chronological feed of significant events, so when someone calls we can instantly see what happened. Built from existing data (no new audit table needed for v1 — derived from timestamps and logs across models):

- **Business timeline**: created, onboarding completed, verified/rejected, subscription changes (tier change, trial started/expired, cancelled), handle changes, Stripe Connect setup, bookings created/cancelled/completed, suspension/reactivation
- **User timeline**: registered, email verified, businesses created, staff invites accepted, bookings made (as customer), impersonation sessions

### Admin actions on business detail page
- Verify / reject (with notes)
- Suspend / reactivate (with reason)
- Change subscription tier (free ↔ solo ↔ salon)
- Extend or grant trial (set `trial_ends_at`)
- Change handle (bypass cooldown)
- Update business settings (auto_confirm, deposits, etc.)
- View Stripe dashboard links (subscription + Connect account)
- View all locations, services, staff members
- View recent bookings with cancel/reschedule actions
- View verification documents with approve/reject per document

### Admin actions on user detail page
- View activity timeline
- View all associated businesses (owned + staff)
- View all bookings (as customer, across businesses)
- View communication history (emails + SMS sent to this user's email/phone)
- Impersonate (login as user)
- Reset password
- Promote/demote super admin
- Merge stub user into registered user (if duplicate detected)

---

## Architecture Decisions

### Route prefix: `/admin` (not subdomain)
Subdomain routing adds DNS/SSL complexity for no benefit at this scale. `/admin` prefix with `EnsureSuperAdmin` middleware is simpler and consistent with the existing `/cp` (Statamic) pattern.

### Separate Inertia layout
Admin pages use a dedicated `AdminLayout` with its own sidebar navigation, distinct from the business dashboard. Shares the same shadcn/ui component library.

### Admin pages directory
Admin React pages live in `resources/js/pages/admin/` to keep them separate from business dashboard pages in `resources/js/pages/dashboard/`.

### Service-based stats
An `AdminStatsService` handles all platform-level queries, following the `DashboardStatsService` pattern used by the business dashboard.

### Activity timeline — derived, not stored
For v1, timelines are assembled on-the-fly from existing timestamps and related models (no new audit/events table). An `ActivityTimelineService` queries across models and merges into a single chronological feed. If performance becomes an issue later, we can add a denormalised `activity_log` table — but the data already exists.

---

## Phase 12a: Admin Foundation — Routes, Layout & Overview

**Goal:** Establish the admin route group, layout, and overview dashboard with platform-level stats.

### Backend

**Middleware** — Reuse existing `EnsureSuperAdmin` middleware. No changes needed.

**Routes** (`routes/web.php`) — New route group:
```
/admin                                        → AdminDashboardController (overview)
/admin/businesses                             → AdminBusinessController@index
/admin/businesses/{business}                  → AdminBusinessController@show
/admin/businesses/{business}/verify           → AdminBusinessController@verify (POST)
/admin/businesses/{business}/suspend          → AdminBusinessController@suspend (POST)
/admin/businesses/{business}/subscription     → AdminBusinessController@updateSubscription (PATCH)
/admin/businesses/{business}/trial            → AdminBusinessController@updateTrial (PATCH)
/admin/businesses/{business}/handle           → AdminBusinessController@updateHandle (PATCH)
/admin/businesses/{business}/settings         → AdminBusinessController@updateSettings (PATCH)
/admin/businesses/{business}/bookings         → AdminBusinessController@bookings (GET)
/admin/businesses/{business}/bookings/{booking}/cancel   → AdminBookingController@cancel (POST)
/admin/businesses/{business}/bookings/{booking}/reschedule → AdminBookingController@reschedule (POST)
/admin/users                                  → AdminUserController@index
/admin/users/{user}                           → AdminUserController@show
/admin/users/{user}/impersonate               → AdminUserController@impersonate (POST)
/admin/users/{user}/reset-password            → AdminUserController@resetPassword (POST)
/admin/users/{user}/toggle-super              → AdminUserController@toggleSuper (POST)
/admin/impersonate/leave                      → AdminUserController@leaveImpersonation (POST)
```

All wrapped in `['auth', 'verified', EnsureSuperAdmin::class]` middleware.

**AdminDashboardController** (invokable) — Returns Inertia page with platform stats:
- Total businesses (by verification status breakdown)
- Total users (registered vs stub)
- Total bookings (today, this week, this month)
- MRR calculation (active subscriptions × tier price)
- Pending verifications count (prominent — this is the main action item)
- Recent signups (businesses, last 7 days)
- Businesses with expiring trials (next 3 days)

**AdminStatsService** — Encapsulates all platform-level queries:
- `getOverviewStats()`: aggregate counts, MRR, pending verifications
- `getRecentSignups(int $days)`: businesses created in last N days
- `getRevenueBreakdown()`: MRR by tier
- `getSubscriptionBreakdown()`: count by tier × status
- `getExpiringTrials(int $days)`: businesses whose trial ends within N days

### Frontend

**Admin layout** (`resources/js/layouts/admin/admin-sidebar-layout.tsx`):
- Same `AppShell` + `AppContent` pattern as business dashboard
- Admin-specific sidebar with navigation: Overview, Businesses, Users
- No business switcher — admin context is global

**Admin sidebar** (`resources/js/components/admin/admin-sidebar.tsx`):
- Navigation items: Overview (LayoutGrid), Businesses (Building2), Users (Users)
- Header shows "heyBertie Admin"
- Pending verifications count badge on Businesses nav item

**Overview page** (`resources/js/pages/admin/index.tsx`):
- Stat cards: Total Businesses, Active Subscriptions, MRR, Pending Verifications
- Pending verifications list (clickable — links to business detail)
- Expiring trials list (businesses about to lose access)
- Recent signups table (last 7 days)
- Subscription breakdown by tier

### Files to create

| File | Purpose |
|------|---------|
| `app/Http/Controllers/Admin/AdminDashboardController.php` | Overview page |
| `app/Services/AdminStatsService.php` | Platform stats queries |
| `resources/js/layouts/admin/admin-sidebar-layout.tsx` | Admin layout |
| `resources/js/components/admin/admin-sidebar.tsx` | Admin sidebar nav |
| `resources/js/pages/admin/index.tsx` | Overview page |
| `resources/js/types/admin.ts` | Admin-specific TypeScript types |

### Files to modify

| File | Changes |
|------|---------|
| `routes/web.php` | Add admin route group |
| `resources/js/types/index.ts` | Export admin types |

### Verification
- `php artisan test --compact` — no regressions
- `npm run build` — compiles
- Visit `/admin` as super admin — see overview stats
- Visit `/admin` as non-super — 403 forbidden
- Visit `/admin` as guest — redirect to login

---

## Phase 12b: Business Management — List, Detail, Operational Actions

**Goal:** Admin can browse all businesses, drill into a full operational detail page, take actions on behalf of the business (verify, suspend, change plan, manage bookings, etc.).

### Backend

**AdminBusinessController**:
- `index()`: Paginated business list with search (name, handle, email, owner email), filters (verification_status, subscription_tier, is_active, onboarding_completed). Eager loads: owner, subscriptionTier, subscriptionStatus. Returns Inertia page.
- `show(Business $business)`: Full operational detail page. Eager loads: owner, subscriptionTier, subscriptionStatus, verificationDocuments, locations, staffMembers. Includes: quick stats (bookings/customers/revenue counts), recent bookings list, activity timeline.
- `verify(AdminVerifyBusinessRequest)`: POST — approve or reject verification
- `suspend(AdminSuspendBusinessRequest)`: POST — toggle `is_active` with reason
- `updateSubscription(AdminUpdateSubscriptionRequest)`: PATCH — change subscription tier (updates `subscription_tier_id`, `subscription_status_id`)
- `updateTrial(AdminUpdateTrialRequest)`: PATCH — set `trial_ends_at` (extend, grant, or revoke trial)
- `updateHandle(AdminUpdateHandleRequest)`: PATCH — change handle (bypasses 30-day cooldown, still creates `HandleChange` record for redirect)
- `updateSettings(AdminUpdateSettingsRequest)`: PATCH — update business `settings` JSON (auto_confirm, deposits, etc.)
- `bookings(Business $business)`: GET — paginated bookings list for this business with search (reference, customer name/email)

**AdminBookingController**:
- `cancel(Business, Booking, AdminCancelBookingRequest)`: POST — cancel booking on behalf of customer/business, with optional refund
- `reschedule(Business, Booking, AdminRescheduleBookingRequest)`: POST — reschedule to new datetime (bypasses min_notice_hours for admin)

**ActivityTimelineService**:
- `forBusiness(Business $business, int $limit = 50)`: Returns chronological feed assembled from:
  - `businesses.created_at` → "Business created"
  - `businesses.onboarding` JSON → onboarding step completions
  - `businesses.verified_at` → "Verified" / `verification_status` changes
  - `handle_changes` records → "Handle changed from X to Y"
  - Subscription tier/status changes (derived from current state + `trial_ends_at`)
  - `bookings` → "Booking #REF created/confirmed/cancelled/completed"
  - `verification_documents` → "Document uploaded/approved/rejected"
  - `stripe_connect_id` populated → "Stripe Connect setup completed"
- Returns array of `{type, description, timestamp, metadata}` sorted descending

### Frontend

**Business list page** (`resources/js/pages/admin/businesses/index.tsx`):
- Search input (searches name, handle, owner email)
- Filter dropdowns: verification status, subscription tier, active/inactive, onboarding complete/incomplete
- Table: Name, Handle, Owner, Tier, Verification, Active, Created, Actions
- Verification status badge (pending=amber, verified=green, rejected=red)
- Quick-action buttons: Verify (if pending), View
- Pagination

**Business detail page** (`resources/js/pages/admin/businesses/show.tsx`):
This is the primary operational page — everything an admin needs when a business calls for help.

- **Header**: Business name, handle (as link to public listing), verification badge, active/suspended badge
- **Quick stats row**: Total bookings, total customers, total revenue, page views (7d)
- **Info card**: Name, handle, email, phone, website, description, created date
- **Owner card**: Name, email, link to user detail page, "Impersonate" button
- **Subscription card**: Current tier, status, trial end date, Stripe customer ID (link to Stripe dashboard). Action buttons: Change Tier (dropdown), Extend Trial (date picker), Cancel Subscription
- **Stripe Connect card**: Connect status (not started / pending / complete), Connect account ID (link to Stripe Connect dashboard), deposit settings (enabled, type, amount/percentage)
- **Verification section**: Current status with approve/reject form. Documents list with thumbnails, status badges, reviewer info. Per-document approve/reject
- **Locations list**: Name, address, type (salon/mobile), active status, booking rules
- **Services list**: Name, price, duration, active status
- **Staff list**: Name, role, active status, commission rate
- **Recent bookings table**: Last 20 bookings with reference, customer, date, status, price. Cancel/reschedule action buttons on pending/confirmed bookings
- **Activity timeline**: Chronological feed (most recent first) showing all significant events. Each entry has icon, description, timestamp, and optional metadata link
- **Danger zone**: Suspend/reactivate toggle with reason field, confirmation dialog

### Files to create

| File | Purpose |
|------|---------|
| `app/Http/Controllers/Admin/AdminBusinessController.php` | Business list + detail + actions |
| `app/Http/Controllers/Admin/AdminBookingController.php` | Booking cancel/reschedule for admin |
| `app/Services/ActivityTimelineService.php` | Assembles activity feed from models |
| `app/Http/Requests/Admin/AdminVerifyBusinessRequest.php` | Verify validation |
| `app/Http/Requests/Admin/AdminSuspendBusinessRequest.php` | Suspend validation |
| `app/Http/Requests/Admin/AdminUpdateSubscriptionRequest.php` | Tier change validation |
| `app/Http/Requests/Admin/AdminUpdateTrialRequest.php` | Trial change validation |
| `app/Http/Requests/Admin/AdminUpdateHandleRequest.php` | Handle change validation |
| `app/Http/Requests/Admin/AdminUpdateSettingsRequest.php` | Settings validation |
| `app/Http/Requests/Admin/AdminCancelBookingRequest.php` | Cancel validation |
| `app/Http/Requests/Admin/AdminRescheduleBookingRequest.php` | Reschedule validation |
| `resources/js/pages/admin/businesses/index.tsx` | Business list |
| `resources/js/pages/admin/businesses/show.tsx` | Business detail |
| `resources/js/components/admin/activity-timeline.tsx` | Reusable timeline component |

### Verification
- List page shows all businesses with filters working
- Can approve a pending business — status changes to verified, `verified_at` set
- Can reject a pending business — status changes to rejected with notes
- Can suspend an active business — `is_active` becomes false
- Can reactivate a suspended business
- Can change subscription tier — tier updates immediately
- Can extend trial — `trial_ends_at` updates, business regains access
- Can change handle — handle updates, old handle redirects
- Can cancel a booking from business detail — booking cancelled, deposit refunded if applicable
- Activity timeline shows events in correct chronological order
- Verification documents display with per-document approve/reject

---

## Phase 12c: User Management, Impersonation & Communication History

**Goal:** Admin can browse users, see their full history (activity timeline + communication logs), take actions (impersonate, reset password, manage roles), and resolve customer support issues.

### Backend

**AdminUserController**:
- `index()`: Paginated user list with search (name, email), filters (role, is_registered, super, has_businesses). Returns Inertia page.
- `show(User $user)`: Full user profile. Eager loads: ownedBusinesses.subscriptionTier, businesses, pets. Includes: activity timeline, communication history, bookings (as customer across all businesses).
- `impersonate(User $user)`: POST — stores original admin user ID in session, logs in as target user. Cannot impersonate other super admins.
- `leaveImpersonation()`: POST — restores original admin session.
- `resetPassword(User $user, AdminResetPasswordRequest)`: POST — sets new password, invalidates sessions. Sends password reset notification to user.
- `toggleSuper(User $user)`: POST — toggle `super` flag. Cannot remove own super access.

**ActivityTimelineService** (extends from 12b):
- `forUser(User $user, int $limit = 50)`: Returns chronological feed assembled from:
  - `users.created_at` → "Account created"
  - `users.email_verified_at` → "Email verified"
  - `users.is_registered` change → "Upgraded from guest to registered"
  - `businesses.created_at` where `owner_user_id = user` → "Created business X"
  - `business_user.accepted_at` → "Joined business X as staff"
  - Bookings via `customers.user_id` → "Booked at Business X (ref #REF)" / "Cancelled booking #REF"
  - `users.last_login` → "Last login" (single entry, not historical — noted as limitation)

**Communication history** — queried from `email_log` and `sms_log`:
- Filter by user's email (across all `to_email` in email_log)
- Filter by user's phone via associated Customer records (across `phone_number` in sms_log)
- Returns: type (email/sms), subject/message_type, status (sent/delivered/failed/opened), timestamp, associated booking reference

**Impersonation session keys**:
- `impersonating_from`: original admin user ID
- `impersonating_from_name`: original admin name (for banner display)

### Frontend

**User list page** (`resources/js/pages/admin/users/index.tsx`):
- Search input (name, email)
- Filter dropdowns: role, registered/guest, super admin, has businesses
- Table: Name, Email, Role, Registered, Super, Businesses (count), Created, Actions
- Badge for stub/guest users
- Link to detail page
- Pagination

**User detail page** (`resources/js/pages/admin/users/show.tsx`):
This is what we look at when a customer or business owner calls in.

- **Header**: Name, email, role badge, registered/guest badge, super badge, "Impersonate" button
- **Info card**: Email, role, registered status, 2FA enabled, created date, last login
- **Owned businesses**: List with name, handle, tier, verification status — links to admin business detail
- **Staff memberships**: Businesses where user is staff (via `business_user` pivot), with role and active status
- **Pets**: List of pets (name, breed, size) — useful for booking support queries
- **Bookings as customer**: Recent bookings across all businesses (via Customer records linked to this user). Shows: business name, reference, date, status, price. Cancel/reschedule actions on active bookings
- **Communication history tab**: Combined email + SMS log for this user, sorted by date. Shows: type icon, subject/message_type, delivery status badge, booking reference link, timestamp
- **Activity timeline**: Chronological feed of all user events
- **Actions section**: Reset password (with confirmation), toggle super admin (with confirmation), merge with another user (future — disabled for v1)

**Impersonation banner** (`resources/js/components/admin/impersonation-banner.tsx`):
- Fixed banner at top of page when `impersonating_from` is in shared props
- Shows "You are impersonating {name}" with "Leave" button
- Visible on ALL pages (business dashboard, marketing, etc.)

### Files to create

| File | Purpose |
|------|---------|
| `app/Http/Controllers/Admin/AdminUserController.php` | User management + actions |
| `app/Http/Requests/Admin/AdminResetPasswordRequest.php` | Password reset validation |
| `resources/js/pages/admin/users/index.tsx` | User list |
| `resources/js/pages/admin/users/show.tsx` | User detail |
| `resources/js/components/admin/impersonation-banner.tsx` | Impersonation banner |
| `resources/js/components/admin/communication-log.tsx` | Email/SMS history component |

### Files to modify

| File | Changes |
|------|---------|
| `app/Http/Middleware/HandleInertiaRequests.php` | Share `impersonating` prop (from/name) when session has impersonation data |
| `app/Services/ActivityTimelineService.php` | Add `forUser()` method (created in 12b) |
| `resources/js/layouts/app/app-sidebar-layout.tsx` | Render impersonation banner when prop present |
| `resources/js/layouts/admin/admin-sidebar-layout.tsx` | Render impersonation banner when prop present |

### Verification
- User list shows all users with correct search and filters
- User detail shows owned businesses, staff memberships, pets
- Bookings as customer shows bookings across multiple businesses
- Communication history shows emails and SMS with delivery status
- Activity timeline shows chronological events
- Can impersonate a non-super user — logged in as them, banner visible
- Cannot impersonate another super admin — blocked
- "Leave impersonation" restores original admin session
- Impersonation banner visible on business dashboard pages too
- Can reset user password — user receives notification
- Can toggle super admin — flag updates (cannot remove own super)

---

## Phase 12d: Business Settings Page (Business Dashboard)

**Goal:** Business owners can manage their settings from the dashboard. Initial setting: auto-confirm bookings toggle (backend already wired in Phase 7b).

### Backend

**BusinessSettingsController**:
- `index()`: Returns Inertia settings page with current business settings JSON
- `update(UpdateBusinessSettingsRequest $request)`: Updates `settings` JSON column. Merges incoming settings with existing (doesn't overwrite unrelated keys).

**Form Request** (`UpdateBusinessSettingsRequest`):
- Validates `auto_confirm_bookings` (boolean)
- Extensible for future settings (deposit amount, cancellation policy window, etc.)

**Route**: `/{handle}/settings` — added to existing business dashboard route group.

### Frontend

**Settings page** (`resources/js/pages/dashboard/settings/index.tsx`):
- Card-based layout with setting groups
- "Bookings" section: auto-confirm toggle with description ("Automatically confirm new bookings instead of requiring manual approval")
- Save button with success toast

### Files to create

| File | Purpose |
|------|---------|
| `app/Http/Controllers/Dashboard/BusinessSettingsController.php` | Settings CRUD |
| `app/Http/Requests/Dashboard/UpdateBusinessSettingsRequest.php` | Validation |
| `resources/js/pages/dashboard/settings/index.tsx` | Settings page |

### Files to modify

| File | Changes |
|------|---------|
| `routes/web.php` | Add settings routes to business dashboard group |
| `resources/js/components/app-sidebar.tsx` | Add "Settings" nav item (Settings icon) |

### Verification
- Settings page loads with current auto_confirm_bookings value
- Toggle and save — setting persists in database
- Create a booking — confirms automatically when toggle is on
- Create a booking — stays pending when toggle is off
- Other settings in the JSON column are preserved (not overwritten)

---

## Execution Order

| Sub-phase | Scope | Depends on |
|-----------|-------|-----------|
| **12a** | Admin routes, layout, overview dashboard | — |
| **12b** | Business management + activity timeline + operational actions | 12a |
| **12c** | User management, impersonation, communication history | 12a, 12b (reuses ActivityTimelineService) |
| **12d** | Business settings page (business dashboard) | — (independent) |

12a must come first (establishes admin infrastructure). 12b creates the `ActivityTimelineService` and `activity-timeline.tsx` component that 12c reuses for user profiles. 12d is completely independent (business dashboard, not admin) and can be done in any order.

## Verification (per sub-phase)
1. `vendor/bin/pint --dirty --format agent`
2. `npm run build` — frontend compiles
3. `php artisan test --compact` — no regressions
4. Visual inspection + manual testing guide per sub-phase
