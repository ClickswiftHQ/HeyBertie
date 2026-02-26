# heyBertie Development Roadmap

> High-level progress tracker. Detailed specs live in [`roadmap/`](roadmap/).

**Platform principle:** heyBertie is a sector-agnostic pet services marketplace. Dog grooming is the initial launch vertical, but the schema, terminology, and features must remain generic enough to extend to other sectors (cat sitting, dog walking, pet boarding, mobile vets, etc.) without structural rewrites. Avoid grooming-specific assumptions in models, UI copy, and business logic.

---

## Phase 0: Foundation & Setup (Week 1) - COMPLETE

- [x] Domain & infrastructure (DNS, SSL, Herd)
- [x] Project initialization (Laravel, Breeze, shadcn/ui, Vite, Alpine.js)
- [x] Database setup (PostgreSQL)
- [x] Statamic CMS installation
- [ ] Legal & compliance (trademark, company, bank account)

---

## Phase 1: Marketing Website - Homepage (Week 2) - COMPLETE

- [x] Marketing layout (header, footer, mobile menu, sticky header)
- [x] Homepage sections (hero, search, trust bar, how it works, services, cities, CTA)
- [x] Routes configuration
- [x] Responsive testing (mobile/tablet/desktop)

---

## Phase 2: Core Database Schema (Week 2-3) - COMPLETE

> **Detailed spec:** [`roadmap/phase-2-database-schema.md`](roadmap/phase-2-database-schema.md)

- [x] Migrations (15 tables: businesses, locations, services, bookings, customers, staff, reviews, availability, transactions, comms logs — Cashier deferred to Phase 7)
- [x] Models with relationships, casts, scopes (13 models)
- [x] Services (HandleService, BookingService, AvailabilityService, GeocodingService, SmsQuotaService, TransactionLogger)
- [x] Seeders (8 seeders with realistic demo data)
- [x] Verification (migrate:fresh, db:seed, 49 relationship/scope/isolation tests passing)

### Phase 2b: Schema Refactor — Lookup Tables, Pets & Stub Users - COMPLETE

- [x] Lookup tables: `subscription_tiers`, `subscription_statuses`, `business_roles` (replace enums with FK references)
- [x] Taxonomy tables: `species`, `size_categories`, `breeds`
- [x] Pets table: belongs to `users` (not customers), enabling cross-business pet data
- [x] Stub users: `is_registered` flag on users, `user_id` now NOT NULL on customers — walk-ins get a stub User record
- [x] Customer refactor: removed pet columns, added `source` and `marketing_consent`
- [x] 7 new models (SubscriptionTier, SubscriptionStatus, BusinessRole, Species, SizeCategory, Breed, Pet)
- [x] Updated all factories, seeders, services, and tests (109 tests passing)

### Phase 2c: Customer Registration Service & Pet Data Visibility - COMPLETE

**Decision: Three-Tier Pet Data Visibility Model**

| Tier | Ownership | Visibility | Examples |
|------|-----------|------------|----------|
| Pet Profile | Customer (platform) | Customer + businesses they book with (after opt-in) | Name, breed, weight, DOB, allergies |
| Business-Pet Notes | Business-scoped | That business's staff only | Private notes, difficulty rating |
| Visit Notes | Business-scoped, per booking | Staff (already exists as `pro_notes` on Booking) | "Used #4 blade", "matting behind ears" |

- [x] `business_pet` pivot table with business-scoped notes, difficulty rating, and last_seen_at
- [x] `BusinessPet` model with relationships and factory
- [x] `Business::pets()` BelongsToMany, `Pet::businesses()` BelongsToMany, `Pet::businessNotes()` HasMany
- [x] `CustomerRegistrationService` — find-or-create user by email when a business adds a customer
- [x] If stub user already exists, reuse it (pets and history follow the person across businesses)
- [x] If no match, create a stub user (`is_registered = false`)
- [x] Handle account upgrade: `upgradeStubUser()` sets `is_registered = true`, password, `email_verified_at`
- [x] `linkPetToBusiness()` — updateOrCreate on business_pet with notes and last_seen_at
- [x] Updated PetSeeder with BusinessPet records
- [x] 19 new tests (6 model, 11 service, 2 data isolation) — 128 total passing

### Phase 2d: Schema Hardening - COMPLETE

> Post-retrospective fixes identified from a full audit of Phase 2a–2c.

**Missing indexes (performance at scale):**
- [x] `bookings[business_id, status, appointment_datetime]` — dashboard/calendar queries
- [x] `bookings[business_id, appointment_datetime]` — upcoming/past scopes
- [x] `customers[user_id]` — `CustomerRegistrationService` lookups
- [x] `transactions[business_id, status, created_at]` — financial reporting
- [x] `sms_log[business_id, status, created_at]` — quota checks after every SMS
- [x] `email_log[business_id, status, created_at]` — delivery tracking
- [x] `reviews[business_id, is_flagged]` — moderation queue

**Data integrity:**
- [x] Unique constraints on `businesses.stripe_customer_id` and `businesses.stripe_subscription_id`
- [x] Add soft deletes to `Booking`, `Customer`, `Service`, `StaffMember`

**Code fixes:**
- [x] Batch `Customer.updateFromBooking()` into a single query (was 4 queries, now 1)
- [x] Move `AvailabilityService` overlap detection from PHP to database interval query (`isTimeSlotAvailable` now uses direct DB queries; `getAvailableSlots` keeps bulk-fetch for efficiency)
- [x] Refactor `StaffMember.working_locations` from JSON array to `staff_location` junction table with `BelongsToMany` relationship
- [x] 10 new tests (4 soft deletes, 2 batched query, 4 junction table) — 138 total passing

**Architecture decision: Customer/User denormalization — RESOLVED (Option A)**

`Customer` keeps its own `name`, `email`, `phone` as business-local data. `User` is the platform identity. These are intentionally separate contexts: a customer booking through heyBertie (marketplace) doesn't imply the business uses heyBertie to manage their CRM. Businesses can add customers manually (walk-ins, phone) with standalone records. The `user_id` FK links the two for cross-business identity, but each business controls their own customer data independently.

---

## Phase 3: Professional Onboarding (Week 3-4) - COMPLETE

> **Detailed spec:** [`roadmap/phase-3-professional-onboarding.md`](roadmap/phase-3-professional-onboarding.md)

- [x] 7-step onboarding flow (Inertia + React)
- [x] Handle system (validation, uniqueness, reserved words)
- [x] Verification system (photo upload, admin review queue)

---

## Phase 4: Business Listing Page (Week 4-5) - COMPLETE

> **Detailed spec:** [`roadmap/phase-4-business-listing-page.md`](roadmap/phase-4-business-listing-page.md)

- [x] Listing template (header, gallery, services, reviews, availability, CTA)
- [x] BusinessController (handle lookup, eager loading, analytics)
- [x] URL structure (`/{handle}`, `/{handle}/{location}`, `/p/{slug}-{id}` canonical redirect)
- [x] Schema markup (LocalBusiness, Service, Review, Organization)

### Phase 4a: Blade Listing Pages - COMPLETE

- [x] Converted listing pages from Inertia/React to Blade (SEO: server-rendered HTML)
- [x] Marketing layout with header, footer, Alpine.js interactivity
- [x] Listing partials (header, about, services, reviews, location, contact, CTA, availability, share dialog)
- [x] Location switcher for multi-location businesses
- [x] Page view tracking (BusinessPageView, dedup within 30 min)
- [x] SchemaMarkupService (LocalBusiness JSON-LD)
- [x] HandleRedirect middleware (old handle → 301 to new handle)
- [x] 34 tests (routes, data, schema, page views)

### Phase 4b: Hub-and-Spoke Multi-Location SEO - COMPLETE

- [x] Hub page at `/{handle}` for multi-location businesses (Organization schema, location cards grid)
- [x] Self-referencing canonical URLs on each location page (`/{handle}/{slug}`)
- [x] `branchOf` in LocalBusiness schema linking back to Organization hub
- [x] `/p/{slug}-{id}` flipped parameter order, now 301 redirects to `/{handle}`
- [x] Single-location businesses render full listing at `/{handle}` (unchanged)
- [x] 34 tests (hub routing, canonical redirects, schema markup, self-referencing URLs)

---

## Phase 5: Dashboard - Solo Tier (Week 5-7)

- [ ] Dashboard layout (sidebar, business switcher, stats)
- [ ] Calendar/appointments (list view, calendar view, CRUD)
- [ ] Customer/CRM (search, pet profiles, history, loyalty)
- [ ] Services management (list, reorder, toggle)
- [ ] Availability setup (hours, breaks, holidays, buffer)
- [ ] Basic analytics (revenue, bookings, no-show rate)

---

## Phase 5b: Location Town Column - COMPLETE

- [x] Migration: `town` column on `locations`, unique `(business_id, slug)` index
- [x] `Location::generateSlug(town, city)` — deduplicates when town equals city
- [x] Onboarding flow: separate Town / Area and City fields
- [x] Validation, controller, factory, 5 unit tests + updated feature tests (248 passing)

---

## Phase 6: Search Results Page (Week 7-8)

> **Detailed spec:** [`roadmap/phase-6-search-results-page.md`](roadmap/phase-6-search-results-page.md)

- [ ] SearchController with geocoding, distance sort, filters
- [ ] Search results Blade page (result cards, filters sidebar, pagination)
- [ ] SearchService (location query, distance calculation, filtering)
- [ ] SEO landing pages (`/dog-grooming-in-london`, `/{service}-in-{location}`)
- [ ] Schema markup (SearchResultsPage, ItemList)

---

## Phase 7: Customer Booking Flow (Week 8-9) - COMPLETE

> **Detailed spec:** [`roadmap/phase-7-customer-booking-flow.md`](roadmap/phase-7-customer-booking-flow.md)

- [x] Booking page (`/{handle}/{location-slug}/book`) — Blade + Alpine.js 4-step wizard
- [x] Service selection step (multi-service basket with running totals)
- [x] Staff selection step (conditional, "Anyone available" default)
- [x] Date & time selection (date slider, time slot grid grouped by period, API-driven)
- [x] Guest checkout (name, email, phone, pet details — stub user creation)
- [x] `booking_items` table (itemised services with price/duration snapshots)
- [x] `bookings` table additions (booking_reference, pet_name, pet_breed, pet_size, nullable service_id)
- [x] Listing page service "Add" buttons + sticky basket bar enhancement
- [x] API endpoints (available dates, time slots)
- [x] `AvailabilityService.getAvailableDates()`, `BookingService.createMultiServiceBooking()`
- [x] Confirmation page with booking reference
- [x] 29 tests (page rendering, booking creation, API endpoints, validation, edge cases)
- [x] Booking confirmation email (queued Mailable to customer + notification to business)
- [x] Customer booking management (view bookings, cancel, reschedule with 24hr policy, signed URL guest access)
- [x] Stub user upgrade on registration (anti-email-enumeration, case-insensitive)
- [x] Breed autosuggest (API + inline autocomplete on booking form)
- [x] Intent-aware verification redirect (customer → /my-bookings, business → /onboarding)

### Phase 7b: Booking Confirmation & Reminders

- [ ] Auto-confirm setting: business toggle in settings (`auto_confirm_bookings`, default true)
- [ ] Manual confirm: new bookings created as `pending`, business confirms → `confirmed`
- [ ] Auto-confirm: new bookings created directly as `confirmed`
- [ ] Booking reminders (24hr email, 2hr SMS) — scheduled task, populate `reminder_sent_at` fields

---

## Phase 8: Payment Integration (Week 9-10)

- [ ] Stripe setup (Cashier, webhooks, test mode)
- [ ] Subscription management (checkout, portal, webhooks)
- [ ] Booking deposits (Stripe Payment Intents, refunds)
- [ ] Transaction fees (2.5% platform fee, Stripe Connect)

---

## Phase 9: Notification System (Week 10)

- [ ] Email setup (Postmark/SES, templates, queues)
- [ ] SMS setup (Twilio, templates, quota tracking)
- [ ] Automated reminders (24hr email, 2hr SMS)

---

## Phase 10: Admin Dashboard (Week 11)

- [ ] Admin layout (subdomain routing, middleware)
- [ ] Business management (list, verify, suspend)
- [ ] Platform analytics (MRR, churn, approvals)
- [ ] User management (list, roles, impersonate)
- [ ] Business settings UI: auto-confirm bookings toggle (`settings.auto_confirm_bookings` — backend already wired in Phase 7b)

---

## Phase 11: Reviews System (Week 11-12)

- [ ] Review submission (public form, automated request)
- [ ] Review management (respond, flag, notifications)
- [ ] Review display (listing page integration)

---

## Phase 12: Salon Tier Features (Week 12-13)

- [ ] Multi-staff management (calendars, commission)
- [ ] Multi-location management (up to 3, location-specific services)
- [ ] Advanced CRM (tags, birthday reminders, loyalty program)
- [ ] Marketing automation ("we miss you", welcome sequence)
- [ ] Advanced analytics (staff performance, peak times, retention)

---

## Phase 13: Marketing Tools & SEO (Week 13-14)

- [ ] Calculators (no-show cost, pricing, admin time audit)
- [ ] Blog setup (Statamic collection, initial posts)
- [ ] Help center (Statamic, articles, search)
- [ ] SEO optimization (sitemap, schema, OG tags, speed)

---

## Phase 14: Launch Preparation (Week 15)

- [ ] Legal pages (T&Cs, privacy, cookie, refund policies)
- [ ] Cross-browser & mobile testing
- [ ] Analytics & monitoring (GA, Sentry, uptime)
- [ ] Backup & security (DB backups, headers, rate limiting)
- [ ] CI/CD pipeline (GitHub Actions: run `php artisan test` before deploy, block on failure)
- [ ] Production seeder safety: add environment guards to `DatabaseSeeder`, move taxonomy data into migrations or a production-safe seeder, ensure `db:seed` never runs fake data in production

---

## Phase 15: Beta Launch (Week 16-17)

- [ ] Beta user recruitment (10-20 groomers)
- [ ] Onboarding improvements (based on feedback)
- [ ] Content creation (photography, icons, brand colors)
- [ ] Marketing setup (social, ads, email templates)

---

## Phase 16: Public Launch (Week 18+)

- [ ] Launch marketing (blog, PR, ads, social)
- [ ] Growth tactics (referral programs, partnerships)
- [ ] Iteration & optimization (A/B testing, analytics, churn analysis)

---

## Ongoing Tasks (Post-Launch)

- **Weekly:** Platform health, business applications, support, metrics, content
- **Monthly:** Financial review, feature releases, customer success, competitor analysis
- **Quarterly:** Major launches, pricing review, strategic planning, tax filings

---

## Success Metrics

| Category | Key Metrics | Target |
|----------|------------|--------|
| Acquisition | Signups/week, conversion rate, CAC | - |
| Activation | Free→Solo conversion, time to first booking | 15%+ |
| Retention | Monthly churn, 6-month retention | <5% churn |
| Revenue | MRR, ARPU, LTV, LTV:CAC | >8:1 ratio |
| Product | Bookings/groomer, no-show reduction, NPS | - |
