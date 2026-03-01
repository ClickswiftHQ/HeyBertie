# Phase 7: Customer Booking Flow — Detailed Specification

## High-Level Description

This phase builds the customer-facing booking flow — the path from browsing services to confirming an appointment. A customer selects one or more services into a basket, optionally picks a staff member, slides through dates to find an available time slot, provides their details, and confirms the booking.

The booking page URL (`/{handle}/{location-slug}/book`) is a **shareable link** — businesses share it on social media, their website, or Google profile to take customers straight into the booking interface with zero friction. It is a self-contained experience that doesn't require the customer to visit the listing page first.

**UX Reference:** Treatwell / Fresha — services tallied in a basket, dedicated booking page with stepped flow, basket summary panel.

## Dependencies

### From Phase 2 (already built)
- `bookings` table + `Booking` model (lifecycle, scopes, relationships)
- `availability_blocks` table + `AvailabilityBlock` model (weekly + one-off schedules)
- `staff_members` table + `StaffMember` model (team, location assignments, accepts_online_bookings)
- `staff_location` pivot table (which staff work at which locations)
- `BookingService` (availability checking, notice requirements, advance booking limits)
- `AvailabilityService` (slot calculation, conflict detection, buffer time)
- `Location` model with `advance_booking_days`, `min_notice_hours`, `booking_buffer_minutes`
- `customers` table with `user_id` FK (supports stub users for guests)
- `Business.settings` JSON column (for business-level configuration)

### From Phase 4 (already built)
- Business listing page at `/{handle}/{location-slug}` with services display
- `sticky-booking-bar.blade.php` partial (Book Now CTA)
- Service cards with pricing, duration, description

### Outputs consumed by later phases
- **Phase 7b (Payments):** Booking created with `payment_status: 'pending'` → Stripe checkout processes payment
- **Phase 8 (Notifications):** Booking confirmation triggers email/SMS to customer and business
- **Phase 9 (Customer Dashboard):** Customer can view, cancel, or reschedule their bookings
- **Phase 10 (Reviews):** Completed bookings trigger review request flow

---

## Features Overview

| # | Feature | Description |
|---|---------|-------------|
| 1 | Service basket (listing page) | "Add" buttons on services, sticky basket bar with tally |
| 2 | Booking page | Dedicated full-page booking flow at `/{handle}/{location-slug}/book` |
| 3 | Service selection step | Browse and select services with running total |
| 4 | Staff selection step | Optional step to choose a specific staff member or "Anyone available" |
| 5 | Date & time selection | Horizontal date slider with time slot grid |
| 6 | Guest checkout | Name, email, phone, pet details — no sign-up required |
| 7 | Booking creation | Multi-service booking with itemised breakdown |
| 8 | Confirmation page | Summary with reference number |
| 9 | Schema: booking_items | Itemised services per booking with price/duration snapshots |
| 10 | API: available dates | Endpoint for greying out unavailable dates |
| 11 | API: time slots | Endpoint for loading slots per date/staff/duration |

---

## Feature 1: Service Basket on Listing Page

### What it does
Enhances the existing business listing page so customers can add services to a basket before navigating to the booking page.

### User flow
1. Customer browses services on `/{handle}/{location-slug}`
2. Each service card has an "Add" button (replaces the current display-only view)
3. Clicking "Add" toggles to a "Remove" button and adds the service to the basket
4. A sticky basket bar slides up from the bottom:
   ```
   ┌──────────────────────────────────────────────────────────────┐
   │  2 services · 1h 15m · £60.00          [ Choose a time → ]  │
   └──────────────────────────────────────────────────────────────┘
   ```
5. "Choose a time" navigates to `/{handle}/{location-slug}/book?services[]=1&services[]=3`

### Technical notes
- Selected service IDs passed via URL query params (not localStorage — shareable and bookmark-safe)
- Enhance existing `sticky-booking-bar.blade.php` — keep the current "Book Now" / "Call" logic for businesses without bookings
- Alpine.js manages basket state on the listing page
- If location doesn't accept bookings (`accepts_bookings: false`) or business is Free tier, no "Add" buttons shown

### Files
- `resources/views/listing/show.blade.php` — add "Add" buttons to service cards
- `resources/views/listing/partials/sticky-booking-bar.blade.php` — basket bar enhancement

---

## Feature 2: Booking Page

### What it does
A dedicated, self-contained booking page at `/{handle}/{location-slug}/book`. This is the **primary shareable link** businesses distribute on social media, their website, Google Business Profile, or in DMs.

### URL
```
/{handle}/{location-slug}/book
/{handle}/{location-slug}/book?services[]=1&services[]=3    (pre-selected)
```

### Layout
- **Blade + Alpine.js** (consistent with public-facing pages)
- **Desktop:** Two-column — left panel (current step), right panel (basket summary)
- **Mobile:** Single column — basket is a collapsible bottom sheet
- Business name, logo, and location shown at the top for context
- Step indicator: Services → Staff → Date & Time → Your Details

### Behaviour
- Page loads all location services, staff (if applicable), and location config in a single server response
- Steps are handled client-side with Alpine.js (no full page reloads between steps)
- Date and time slot fetching is async via API
- "Back" button on each step returns to the previous step without losing data

### Pre-population
- If `?services[]` params present → those services pre-selected on page load
- If no params → Step 1 starts with nothing selected (customer browses all services)

### Files
- `app/Http/Controllers/BookingController.php` — `show()` method
- `resources/views/booking/show.blade.php` — main layout
- `resources/views/booking/partials/step-services.blade.php`
- `resources/views/booking/partials/step-staff.blade.php`
- `resources/views/booking/partials/step-datetime.blade.php`
- `resources/views/booking/partials/step-details.blade.php`
- `resources/views/booking/partials/basket-summary.blade.php`
- `routes/web.php` — booking route registration

---

## Feature 3: Service Selection Step

### What it does
Step 1 of the booking flow. Customer selects one or more services from the business's catalogue.

### Display
- All active services for this location, grouped by category if applicable
- Each service shows: name, description (truncated), duration, formatted price
- "Add" / "Remove" toggle button per service
- Services with `price_type: 'from'` display "From £X"
- Services with `price_type: 'call'` display "Price on request" — can be added but marked as indicative

### Basket updates
- As services are added/removed, the basket panel updates:
  - Item count: "2 services"
  - Total duration: "1h 15m" (sum of `duration_minutes`)
  - Total price: "£60.00" (sum of `price` for fixed-price services)
- "Continue" button disabled until at least 1 service selected

### Technical notes
- Service data loaded server-side in the initial page render (no API call needed)
- Alpine.js manages the selected services array and computes totals
- If coming from listing page with `?services[]` params, those are pre-selected

---

## Feature 4: Staff Selection Step (Conditional)

### What it does
Step 2 of the booking flow. Allows the customer to choose which staff member performs their appointment. **Only shown when enabled by the business.**

### Configuration
- Business setting: `businesses.settings.staff_selection_enabled` (boolean)
- Default: `false` — step is skipped entirely
- No migration needed — `settings` is already a JSON column on `businesses`
- **Free tier:** Booking flow not available at all
- **Solo tier:** Typically `false` (sole operator)
- **Salon tier:** Configurable via dashboard settings

### Display
- **"Anyone available"** option — selected by default, shown first
  - Description: "We'll assign the first available team member"
- Staff member cards (for each active staff at this location who accepts online bookings):
  - Photo (or initials avatar)
  - Display name
  - Role (e.g. "Senior Groomer")
  - Bio snippet (first ~80 chars)

### Behaviour
- Selecting "Anyone available" means the system checks availability across ALL eligible staff
- Selecting a specific staff member filters time slots to only that person's schedule
- "Continue" button (always enabled — "Anyone" is the default selection)

### "Anyone available" logic (backend)
- Query `AvailabilityService::getAvailableSlots()` without a staff filter
- A slot is available if **any** active staff member at the location is free for the full duration
- At booking creation time, the system assigns the first-available staff member for the chosen slot

### Technical notes
- Staff data loaded server-side in initial page render
- If business has no staff members or `staff_selection_enabled` is false → skip straight to Step 3
- Staff selection stored in Alpine.js state

---

## Feature 5: Date & Time Selection

### What it does
Step 3 of the booking flow. Customer picks a date from a horizontal slider, then selects an available time slot.

### Date slider
- Horizontal scrollable strip from **today** to **`location.advance_booking_days`** days ahead
- Each date tile shows:
  - Day name abbreviated (Mon, Tue, Wed...)
  - Date number (1, 2, 3...)
  - Month abbreviated (Feb, Mar...)
- **Greyed-out dates** have no available slots (determined by the available-dates API)
- Today is initially selected (or the first available date)
- Touch-scrollable on mobile, arrow buttons on desktop

### Time slots
- Grid of available start times, loaded via API when a date is clicked
- Slot interval: 30 minutes (9:00, 9:30, 10:00...)
- Each slot's duration = total duration of all selected services
- Grouped into sections: **Morning** (before 12:00), **Afternoon** (12:00–17:00), **Evening** (17:00+)
- Accounts for:
  - Existing confirmed bookings (conflict detection)
  - Breaks and blocked time (from `availability_blocks`)
  - Holidays (specific_date blocks)
  - Buffer time between appointments (`location.booking_buffer_minutes`)
  - Selected staff member's schedule (or any staff if "Anyone")
  - Minimum notice requirement (`location.min_notice_hours`)

### Display states
- **Available slot:** Clickable, highlighted on selection
- **No slots for a date:** "No availability on this date" message
- **No availability at all:** "This location has no available times in the next {X} days"

### "Continue" button
- Disabled until a date AND time slot are selected
- On click → proceed to Step 4

---

## Feature 6: Guest Checkout

### What it does
Step 4 (final step). Customer provides their contact details and pet information to complete the booking. No account registration required.

### Form fields
| Field | Required | Notes |
|-------|----------|-------|
| Full name | Yes | |
| Email | Yes | For confirmation |
| Phone | Yes | For reminders |
| Pet name | Yes | e.g. "Bella" |
| Pet breed | No | e.g. "Cockapoo" |
| Pet size | No | Small / Medium / Large dropdown |
| Notes for the groomer | No | Textarea — special requirements, behavioural notes |

### Behaviour
- If user is **logged in** → pre-fill name, email, phone from their profile
- If user has **previous bookings** with this business → pre-fill pet details from latest booking
- Validation is inline (Alpine.js client-side + server-side on submit)
- "Confirm Booking" button submits the booking

### Guest user handling
- POST request creates the booking
- If email matches an existing registered User → link to that User (no duplicate)
- If email doesn't match → create a stub User with `is_registered: false` (existing pattern)
- Create or find a Customer record for this business + user combination

---

## Feature 7: Booking Creation

### What it does
Backend logic to validate and create a multi-service booking.

### Flow
1. Validate all inputs (services exist and are active, slot is available, required fields present)
2. Re-check availability at submission time (slot may have been taken since page load)
3. Find or create User (stub for guests)
4. Find or create Customer record for this business
5. Create `Booking` record with:
   - `booking_reference` — generated unique code (e.g. "BK-A7X3M2")
   - `duration_minutes` — sum of all selected service durations
   - `price` — sum of all selected service prices
   - `status: 'pending'`
   - `payment_status: 'pending'`
   - `staff_member_id` — selected staff, or first-available if "Anyone"
   - `pet_name`, `pet_breed`, `pet_size` — from checkout form
   - `customer_notes` — from checkout form
6. Create `BookingItem` records for each selected service (with price/duration snapshots)
7. Return confirmation

### Booking reference format
- Pattern: `BK-{6 alphanumeric}` (e.g. `BK-A7X3M2`)
- Generated using random uppercase + digits, checked for uniqueness
- Stored on `bookings.booking_reference` (unique index)

### Race condition handling
- Availability is re-checked inside a database transaction before creating the booking
- If the slot is no longer available → return a 409 Conflict response with a message
- Frontend shows: "Sorry, this slot was just taken. Please choose another time."

### Files
- `app/Services/BookingService.php` — extend `createBooking()` for multi-service
- `app/Http/Requests/StoreBookingRequest.php` — validation rules
- `app/Http/Controllers/BookingController.php` — `store()` method

---

## Feature 8: Confirmation Page

### What it does
Shown after a booking is successfully created. Provides the customer with a summary and reference number.

### Display
- Booking reference prominently displayed (e.g. "BK-A7X3M2")
- Business name and location
- Date and time
- Selected services with prices
- Total duration and price
- Staff member name (if selected)
- Customer name and email
- "A confirmation email has been sent to {email}" (even if emails aren't wired yet — placeholder)
- "Back to {business name}" link → listing page

### URL
```
/{handle}/{location-slug}/book/confirmation?ref=BK-A7X3M2
```

---

## Feature 9: Schema — `booking_items` Table

### Migration

```
booking_items
├── id              (bigIncrements, PK)
├── booking_id      (foreignId → bookings, cascadeOnDelete)
├── service_id      (foreignId → services, restrictOnDelete)
├── service_name    (string) — snapshot at booking time
├── duration_minutes (integer) — snapshot
├── price           (decimal 8,2) — snapshot
├── display_order   (integer, default 0)
├── created_at      (timestamp)
└── updated_at      (timestamp)

Indexes:
  - booking_id
  - service_id
```

**Rationale:** Snapshotting name, duration, and price ensures booking records remain accurate if the business later updates their service catalogue. The original `service_id` FK is kept for reporting and analytics.

### Bookings table additions

| Column | Type | Notes |
|--------|------|-------|
| `booking_reference` | string, unique | e.g. "BK-A7X3M2" |
| `pet_name` | string, nullable | Captured at checkout |
| `pet_breed` | string, nullable | |
| `pet_size` | string, nullable | small / medium / large |

Also: make `service_id` nullable (bookings now use `booking_items` for the service breakdown, but the column is kept for backward compatibility).

### Model: `BookingItem`
- `belongsTo(Booking)`
- `belongsTo(Service)`
- Fillable: booking_id, service_id, service_name, duration_minutes, price, display_order

### Model update: `Booking`
- Add `hasMany(BookingItem)` relationship
- Add `generateReference()` method
- Add `items` relationship accessor

---

## Feature 10: API — Available Dates

### Endpoint
```
GET /api/booking/{location}/available-dates?duration={minutes}&staff={id}
```

### Parameters
| Param | Required | Description |
|-------|----------|-------------|
| `duration` | Yes | Total appointment duration in minutes |
| `staff` | No | Staff member ID — omit for "anyone available" |

### Response
```json
{
  "dates": [
    { "date": "2026-02-23", "available": true },
    { "date": "2026-02-24", "available": false },
    { "date": "2026-02-25", "available": true }
  ],
  "advance_booking_days": 30
}
```

### Backend
- New method: `AvailabilityService::getAvailableDates(Location, ?StaffMember, int $duration, int $daysAhead): array`
- For each date in range: check if `getAvailableSlots()` returns at least 1 result
- Performance: cached per location+staff+duration for 5 minutes (slots change infrequently)

---

## Feature 11: API — Time Slots

### Endpoint
```
GET /api/booking/{location}/time-slots?date={Y-m-d}&duration={minutes}&staff={id}
```

### Parameters
| Param | Required | Description |
|-------|----------|-------------|
| `date` | Yes | Date in `Y-m-d` format |
| `duration` | Yes | Total appointment duration in minutes |
| `staff` | No | Staff member ID — omit for "anyone available" |

### Response
```json
{
  "date": "2026-02-25",
  "slots": [
    { "time": "09:00", "period": "morning" },
    { "time": "09:30", "period": "morning" },
    { "time": "10:00", "period": "morning" },
    { "time": "14:00", "period": "afternoon" },
    { "time": "14:30", "period": "afternoon" }
  ]
}
```

### Backend
- Uses existing `AvailabilityService::getAvailableSlots()` (already handles staff filtering, conflicts, buffers)
- Pass total duration (sum of selected services) as the `$durationMinutes` parameter
- Add `period` grouping (morning/afternoon/evening) in the response mapping

---

## Edge Cases

| Scenario | Behaviour |
|----------|-----------|
| No services selected | "Continue" button disabled on Step 1 |
| All slots taken for a date | Date tile greyed out in slider, "No availability" message if clicked |
| Selected staff has no availability | Message: "No availability for {name}. Try 'Anyone available'?" |
| Location doesn't accept bookings | No "Book Now" or "Add" buttons on listing page; `/book` route returns 404 |
| Free tier business | No booking flow available at all; `/book` route returns 404 |
| Slot taken between selection and submission | 409 response; frontend prompts to pick a new time |
| Guest email matches existing user | Link booking to existing User — no duplicate account |
| Service updated after page load but before submit | Booking items snapshot current prices; minor price difference is acceptable |
| Business has no staff members | Staff step skipped; `staff_member_id` left null on booking |
| Mobile business (no fixed location) | Booking still works — availability is per-location regardless of type |

---

## What This Phase Does NOT Include

| Excluded | Rationale |
|----------|-----------|
| Payment / Stripe integration | Phase 7b — bookings created with `payment_status: 'pending'` |
| Booking confirmation emails/SMS | Notifications phase — placeholder text shown on confirmation |
| Booking management (cancel/reschedule) | Customer dashboard phase |
| Calendar sync (Google/Apple) | Future enhancement |
| Package deals / bundle discounts | Future enhancement |
| Service dependencies / sequencing rules | Future enhancement |
| Per-service staff assignment | Not needed — one groomer handles the full appointment |
| Deposit collection | Phase 7b with Stripe |

---

## Routes

```php
// Booking flow
Route::get('/{handle}/{locationSlug}/book', [BookingController::class, 'show'])->name('booking.show');
Route::post('/{handle}/{locationSlug}/book', [BookingController::class, 'store'])->name('booking.store');
Route::get('/{handle}/{locationSlug}/book/confirmation', [BookingController::class, 'confirmation'])->name('booking.confirmation');

// Booking API
Route::get('/api/booking/{location}/available-dates', [BookingController::class, 'availableDates'])->name('api.booking.available-dates');
Route::get('/api/booking/{location}/time-slots', [BookingController::class, 'timeSlots'])->name('api.booking.time-slots');
```

---

## Files to Create

| File | Purpose |
|------|---------|
| `database/migrations/xxxx_create_booking_items_table.php` | Itemised services per booking |
| `database/migrations/xxxx_add_booking_reference_and_pet_to_bookings.php` | Reference code + pet fields |
| `app/Models/BookingItem.php` | BookingItem Eloquent model |
| `app/Http/Controllers/BookingController.php` | Booking page + API endpoints |
| `app/Http/Requests/StoreBookingRequest.php` | Validation for booking creation |
| `resources/views/booking/show.blade.php` | Booking page layout |
| `resources/views/booking/partials/step-services.blade.php` | Step 1: service selection |
| `resources/views/booking/partials/step-staff.blade.php` | Step 2: staff selection |
| `resources/views/booking/partials/step-datetime.blade.php` | Step 3: date & time picker |
| `resources/views/booking/partials/step-details.blade.php` | Step 4: guest checkout form |
| `resources/views/booking/partials/basket-summary.blade.php` | Right panel basket |
| `resources/views/booking/confirmation.blade.php` | Post-booking confirmation |
| `tests/Feature/Booking/BookingPageTest.php` | Booking page rendering |
| `tests/Feature/Booking/BookingCreationTest.php` | Booking submission + validation |
| `tests/Feature/Booking/AvailableSlotsApiTest.php` | Time slot + date API tests |

## Files to Modify

| File | Changes |
|------|---------|
| `app/Services/AvailabilityService.php` | Add `getAvailableDates()`, accept total duration |
| `app/Services/BookingService.php` | Multi-service creation, guest user handling, reference generation |
| `app/Models/Booking.php` | Add `items()` relationship, `generateReference()` method |
| `routes/web.php` | Register booking routes |
| `resources/views/listing/show.blade.php` | Service "Add" buttons |
| `resources/views/listing/partials/sticky-booking-bar.blade.php` | Basket bar with tally |
