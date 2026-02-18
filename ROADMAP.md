# heyBertie Development Roadmap

> High-level progress tracker. Detailed specs live in [`roadmap/`](roadmap/).

---

## Phase 0: Foundation & Setup (Week 1) - COMPLETE

- [x] Domain & infrastructure (DNS, SSL, Herd)
- [x] Project initialization (Laravel, Breeze, shadcn/ui, Vite, Alpine.js)
- [x] Database setup (PostgreSQL)
- [x] Statamic CMS installation
- [ ] Legal & compliance (trademark, company, bank account)

---

## Phase 1: Marketing Website - Homepage (Week 2)

- [ ] Marketing layout (header, footer, mobile menu, sticky header)
- [ ] Homepage sections (hero, search, trust bar, how it works, services, cities, CTA)
- [ ] Routes configuration
- [ ] Responsive testing (mobile/tablet/desktop)

---

## Phase 2: Core Database Schema (Week 2-3)

> **Detailed spec:** [`roadmap/phase-2-database-schema.md`](roadmap/phase-2-database-schema.md)

- [ ] Migrations (16 tables: businesses, locations, services, bookings, customers, staff, reviews, availability, transactions, comms logs)
- [ ] Models with relationships, casts, scopes (13 models)
- [ ] Services (HandleService, BookingService, AvailabilityService, GeocodingService, SmsQuotaService, TransactionLogger)
- [ ] Seeders (8 seeders with realistic demo data)
- [ ] Verification (migrate:fresh, db:seed, relationship tests, data isolation)

---

## Phase 3: Professional Onboarding (Week 3-4)

> **Detailed spec:** [`roadmap/phase-3-professional-onboarding.md`](roadmap/phase-3-professional-onboarding.md)

- [ ] 7-step onboarding flow (Inertia + React)
- [ ] Handle system (validation, uniqueness, reserved words)
- [ ] Verification system (photo upload, admin review queue)

---

## Phase 4: Business Listing Page (Week 4-5)

> **Detailed spec:** [`roadmap/phase-4-business-listing-page.md`](roadmap/phase-4-business-listing-page.md)

- [ ] Listing template (header, gallery, services, reviews, availability, CTA)
- [ ] BusinessController (handle lookup, eager loading, analytics)
- [ ] URL structure (`/@{handle}`, `/@{handle}/{location}`, canonical fallback)
- [ ] Schema markup (LocalBusiness, Service, Review, Organization)

---

## Phase 5: Dashboard - Solo Tier (Week 5-7)

- [ ] Dashboard layout (sidebar, business switcher, stats)
- [ ] Calendar/appointments (list view, calendar view, CRUD)
- [ ] Customer/CRM (search, pet profiles, history, loyalty)
- [ ] Services management (list, reorder, toggle)
- [ ] Availability setup (hours, breaks, holidays, buffer)
- [ ] Basic analytics (revenue, bookings, no-show rate)

---

## Phase 6: Search Results Page (Week 7-8)

- [ ] Search results template (filters, grid, pagination, loading states)
- [ ] SearchController (location, distance, price, rating, type filters)
- [ ] Geocoding integration (postcode to lat/lng, distance calc)
- [ ] SEO city pages (dynamic routes, meta tags)

---

## Phase 7: Payment Integration (Week 8-9)

- [ ] Stripe setup (Cashier, webhooks, test mode)
- [ ] Subscription management (checkout, portal, webhooks)
- [ ] Booking deposits (Stripe Payment Intents, refunds)
- [ ] Transaction fees (2.5% platform fee, Stripe Connect)

---

## Phase 8: Notification System (Week 9)

- [ ] Email setup (Postmark/SES, templates, queues)
- [ ] SMS setup (Twilio, templates, quota tracking)
- [ ] Automated reminders (24hr email, 2hr SMS)

---

## Phase 9: Admin Dashboard (Week 10)

- [ ] Admin layout (subdomain routing, middleware)
- [ ] Business management (list, verify, suspend)
- [ ] Platform analytics (MRR, churn, approvals)
- [ ] User management (list, roles, impersonate)

---

## Phase 10: Reviews System (Week 10-11)

- [ ] Review submission (public form, automated request)
- [ ] Review management (respond, flag, notifications)
- [ ] Review display (listing page integration)

---

## Phase 11: Salon Tier Features (Week 11-12)

- [ ] Multi-staff management (calendars, commission)
- [ ] Multi-location management (up to 3, location-specific services)
- [ ] Advanced CRM (tags, birthday reminders, loyalty program)
- [ ] Marketing automation ("we miss you", welcome sequence)
- [ ] Advanced analytics (staff performance, peak times, retention)

---

## Phase 12: Marketing Tools & SEO (Week 12-13)

- [ ] Calculators (no-show cost, pricing, admin time audit)
- [ ] Blog setup (Statamic collection, initial posts)
- [ ] Help center (Statamic, articles, search)
- [ ] SEO optimization (sitemap, schema, OG tags, speed)

---

## Phase 13: Launch Preparation (Week 14)

- [ ] Legal pages (T&Cs, privacy, cookie, refund policies)
- [ ] Cross-browser & mobile testing
- [ ] Analytics & monitoring (GA, Sentry, uptime)
- [ ] Backup & security (DB backups, headers, rate limiting)

---

## Phase 14: Beta Launch (Week 15-16)

- [ ] Beta user recruitment (10-20 groomers)
- [ ] Onboarding improvements (based on feedback)
- [ ] Content creation (photography, icons, brand colors)
- [ ] Marketing setup (social, ads, email templates)

---

## Phase 15: Public Launch (Week 17+)

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