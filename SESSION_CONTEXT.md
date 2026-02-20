# Session Context

> Overwritten at the end of each session. Provides immediate context for the next conversation.

## Where we left off

**Phase 2 is fully complete.** Next task is Phase 3: Professional Onboarding (see `ROADMAP.md`).

## Phase 2 status

| Phase | Status |
|-------|--------|
| 2a: Core schema (15 tables, 13 models, 6 services) | Complete |
| 2b: Lookup tables, pets, stub users | Complete |
| 2c: CustomerRegistrationService, BusinessPet, three-tier pet visibility | Complete |
| 2d: Schema hardening (indexes, soft deletes, code fixes) | Complete |

**138 tests passing.** All migrations, seeders, and formatting clean.

## What was done in Phase 2d

- **3 migrations:** performance indexes (7 composite), integrity constraints (2 unique + 4 soft deletes), `staff_location` junction table
- **Customer.updateFromBooking():** Batched from 4 separate queries into 1 using `DB::raw()` expressions
- **AvailabilityService:** `isTimeSlotAvailable()` now uses direct DB queries (availability blocks + interval overlap) instead of generating all slots. `getAvailableSlots()` keeps bulk-fetch approach for efficiency. Booking conflict check uses driver-aware SQL (SQLite for tests, PostgreSQL for production).
- **StaffMember.working_locations:** Replaced JSON column with `staff_location` junction table. Added `locations()` BelongsToMany on StaffMember, `staffMembers()` BelongsToMany on Location. Updated `scopeWorksAtLocation` to use `whereHas`. Updated StaffSeeder.
- **Soft deletes:** Added to Booking, Customer, Service, StaffMember
- **10 new tests** in `tests/Feature/SchemaHardeningTest.php`

## Key decisions made

- **Platform is sector-agnostic** — dog grooming is launch vertical, but schema/UI/logic must stay generic.
- **Three-tier pet data visibility** — Pet Profile (platform), Business-Pet Notes (business-scoped), Visit Notes (per-booking).
- **Customer/User denormalization: Option A** — Customer keeps its own name/email/phone as business-local data. User is the platform identity.
- **AvailabilityService dual strategy** — `getAvailableSlots()` uses bulk-fetch + PHP iteration (optimal for many slots). `isTimeSlotAvailable()` uses direct DB queries (optimal for single-slot checks). Driver-aware SQL for SQLite (tests) vs PostgreSQL (production).