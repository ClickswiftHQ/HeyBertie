# Session Context

> Overwritten at the end of each session. Provides immediate context for the next conversation.

## Where we left off

**Phase 3 (Professional Onboarding) is complete.** The full 7-step onboarding wizard is functional with backend, frontend, and tests. Two post-implementation improvements were also made: folder reorganization for multi-vertical support and a `/join` business registration route.

Next task is **Phase 4: Business Listing Page** (see `roadmap/phase-4-business-listing-page.md`, which the user opened at end of session).

## Phase 3 status

| Item | Status |
|------|--------|
| 3a: Backend (migrations, models, service, controller, form requests, middleware, routes) | Complete |
| 3b: Frontend (layout, components, 7 step pages + review) | Complete |
| Folder reorganization (shared/ + grooming/ for multi-vertical) | Complete |
| `/join` business registration route | Complete |
| Email verification enforcement (`MustVerifyEmail`) | Complete |
| `onboarding.complete` middleware on dashboard | Complete |

**182 tests passing (44 onboarding tests).** All migrations, formatting, and builds clean.

## What was done in Phase 3

### Backend
- **2 migrations:** `onboarding` JSON + `onboarding_completed` boolean on businesses; `verification_documents` table
- **VerificationDocument model** with factory, scopes (`pending`, `approved`, `rejected`, `ofType`)
- **OnboardingService** — 7-step state machine: `createDraft`, `saveStep`, `getCurrentStep`, `canAccessStep`, `finalize` (DB transaction creating Location, Services, business_user pivot, subscription)
- **7 Form Request classes** in `app/Http/Requests/Onboarding/`
- **OnboardingController** — `index`, `show`, `store`, `checkHandle`, `review`, `submit`
- **EnsureOnboardingComplete middleware** — redirects users with draft businesses to onboarding
- **Custom RegisterResponse** (`app/Http/Responses/RegisterResponse.php`) — checks session for `registration_intent=business`, redirects to `/onboarding` instead of `/dashboard`

### Frontend (resources/js/pages/onboarding/)
- **shared/** — step-1-business-type, step-3-handle, step-6-verification, step-7-plan, review
- **grooming/** — step-2-business-details, step-4-location, step-5-services
- **Layout** (`onboarding-layout.tsx`) — clean layout with progress bar, save & exit
- **Components** — `progress-bar.tsx`, `step-navigation.tsx`

### Routes
- `GET/POST /onboarding/step/{step}` — 7-step wizard
- `POST /onboarding/check-handle` — real-time handle availability (throttled)
- `GET /onboarding/review` + `POST /onboarding/submit` — review and finalize
- `GET /join` — business registration entry point (sets session intent, renders register page with business branding)

### Key fixes applied during implementation
- **Stale onboarding data:** Added `$business->refresh()` after step-specific save in `saveStep()`
- **Draft UNIQUE constraint:** Draft businesses use `draft-{random}` for handle/slug to avoid collisions
- **Email verification:** Enabled `MustVerifyEmail` on User model (was commented out)
- **Post-verification redirect:** `onboarding.complete` middleware on dashboard catches users who verified email after `/join` registration

## Key decisions made

- **Multi-vertical folder structure:** Onboarding pages split into `shared/` (generic) and `grooming/` (vertical-specific). Controller resolves which page to render. Future verticals get their own subfolder.
- **`/join` vs `/register`:** `/join` is the business registration path. Sets `registration_intent=business` in session. Same form, different copy ("List your business on heyBertie"). After registration → email verification → dashboard → `onboarding.complete` middleware → onboarding.
- **Homepage "Join as a Professional" button** now points to `/join` route.

## Files created/modified this session

### New files
- `app/Http/Controllers/OnboardingController.php`
- `app/Http/Middleware/EnsureOnboardingComplete.php`
- `app/Http/Requests/Onboarding/Store*.php` (7 files)
- `app/Http/Responses/RegisterResponse.php`
- `app/Models/VerificationDocument.php`
- `app/Services/OnboardingService.php`
- `database/factories/VerificationDocumentFactory.php`
- `database/migrations/*_add_onboarding_to_businesses_table.php`
- `database/migrations/*_create_verification_documents_table.php`
- `resources/js/layouts/onboarding-layout.tsx`
- `resources/js/components/onboarding/progress-bar.tsx`
- `resources/js/components/onboarding/step-navigation.tsx`
- `resources/js/pages/onboarding/shared/*.tsx` (5 files)
- `resources/js/pages/onboarding/grooming/*.tsx` (3 files)
- `tests/Feature/Onboarding/*.php` (4 test files)

### Modified files
- `app/Models/Business.php` — onboarding fields, verificationDocuments relationship
- `app/Models/User.php` — implements MustVerifyEmail
- `app/Providers/FortifyServiceProvider.php` — custom RegisterResponse binding, intent prop on register view
- `bootstrap/app.php` — onboarding.complete middleware alias
- `routes/web.php` — onboarding routes, /join route, onboarding.complete on dashboard
- `resources/js/pages/auth/register.tsx` — accepts intent prop, business-specific copy
- `resources/views/marketing/home.blade.php` — "Join as a Professional" → /join
