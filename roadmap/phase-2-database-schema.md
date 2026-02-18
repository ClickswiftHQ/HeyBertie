# Phase 2: Core Database Schema - Detailed Specification

## High-Level Description

This phase establishes the foundational data architecture for heyBertie. The schema supports the core user journey: Users create Businesses, Businesses have Locations, Locations receive Bookings.

## Core Architecture Principle

```
User (person)
  └─> owns/manages Business (legal entity: "Muddy Paws Grooming")
        └─> has Locations (physical: "Fulham Salon", "Chelsea Mobile")
              └─> offers Services (product: "Full Groom - £45")
              └─> has Availability (calendar: "Mon-Fri 9am-5pm")
              └─> receives Bookings (transaction: "Sarah's dog at 2pm")
```

## Key Design Decisions

### 1. Multi-Tenancy via Business
- One user can own multiple businesses (franchises, separate brands)
- One business can have multiple staff members (via pivot table)
- Data isolation: Each business only sees their own data

### 2. Location-Centric Bookings
- Bookings belong to Locations, not Businesses
- Enables multi-location businesses with separate calendars
- Supports both salon (fixed address) and mobile (service radius) models

### 3. Handle System for SEO
- Each business gets a unique handle (e.g., @muddy-paws)
- Handle creates branded URLs: bertie.co.uk/@muddy-paws
- Canonical URLs (/p/{id}-{slug}) prevent broken links if handle changes

### 4. Subscription Tiers Determine Features
- **Free:** 1 location, basic listing, no booking system
- **Solo:** 1 location, booking calendar, CRM, payments
- **Salon:** 5 staff calendars, 3 locations (+ £15/month each), loyalty program

---

## Features Overview

| # | Feature | Description |
|---|---------|-------------|
| 1 | User & Business Management | Users create/own business profiles with multi-business and multi-staff support |
| 2 | Handle & URL System | Unique business handles for branded URLs with canonical fallbacks |
| 3 | Location Management | Support salon-based, mobile service, and hybrid grooming businesses |
| 4 | Service Catalog | Define services offered at each location with flexible pricing models |
| 5 | Booking System | Complete appointment lifecycle from creation to completion |
| 6 | Customer/CRM System | Track customers, their pets, and appointment history per business |
| 7 | Staff Management | Multi-staff support for Salon tier with individual calendars and commission tracking |
| 8 | Availability & Scheduling | Define when businesses accept bookings with recurring schedules and exceptions |
| 9 | Review & Rating System | Customer reviews tied to verified bookings with business responses |
| 10 | Payment & Subscription Tracking | Subscription management and booking payment tracking |
| 11 | Communication Logging | Track SMS and email usage for quota management and billing |

---

## Feature 1: User & Business Management

### Purpose

Users (people) create and manage Businesses (legal entities). Supports solo groomers, multi-business owners, and team-based salons.

### User Journey

1. User registers via Breeze (already exists)
2. During onboarding, user creates their first Business
3. User can later add more businesses (franchises, separate brands)
4. User can invite staff to join their business

### Database Tables

#### 1.1 Users Table (Already Exists)

**File:** Migration already created by Breeze
**Action:** Verify existing schema, add role column if needed

```php
Schema::table('users', function (Blueprint $table) {
    $table->string('role')->default('customer'); // customer, pro, admin
    // Existing: id, name, email, password, email_verified_at, remember_token, timestamps
});
```

**Columns:**
- `id` - Primary key
- `name` - Full name
- `email` - Unique email
- `password` - Hashed password
- `role` - Enum: `customer` (default), `pro` (business owner), `admin` (platform admin)
- `email_verified_at` - Timestamp
- Standard Laravel timestamps

#### 1.2 Businesses Table

**File:** `database/migrations/YYYY_MM_DD_create_businesses_table.php`
**Purpose:** Core business entity with subscription and branding info

```php
Schema::create('businesses', function (Blueprint $table) {
    $table->id();

    // Basic Info
    $table->string('name'); // e.g., "Muddy Paws Grooming"
    $table->string('handle')->unique(); // e.g., "muddy-paws" (@muddy-paws)
    $table->string('slug'); // e.g., "muddy-paws-grooming" (for canonical URLs)
    $table->text('description')->nullable();

    // Branding
    $table->string('logo_url')->nullable();
    $table->string('cover_image_url')->nullable();
    $table->string('phone')->nullable();
    $table->string('email')->nullable();
    $table->string('website')->nullable();

    // Subscription
    $table->enum('subscription_tier', ['free', 'solo', 'salon'])->default('free');
    $table->enum('subscription_status', ['trial', 'active', 'past_due', 'cancelled', 'suspended'])->default('trial');
    $table->timestamp('trial_ends_at')->nullable();
    $table->string('stripe_customer_id')->nullable();
    $table->string('stripe_subscription_id')->nullable();

    // Verification
    $table->enum('verification_status', ['pending', 'verified', 'rejected'])->default('pending');
    $table->text('verification_notes')->nullable(); // Admin notes
    $table->timestamp('verified_at')->nullable();

    // Ownership
    $table->foreignId('owner_user_id')->constrained('users')->onDelete('cascade');

    // Metadata
    $table->boolean('is_active')->default(true);
    $table->json('settings')->nullable(); // JSON for flexible settings

    $table->timestamps();
    $table->softDeletes();

    // Indexes
    $table->index('handle');
    $table->index(['owner_user_id', 'subscription_tier']);
});
```

**Columns:**
- **Business Identity:** `name`, `handle` (unique URL-friendly, 3-30 chars), `slug`, `description`
- **Branding:** `logo_url`, `cover_image_url`, contact details (phone, email, website)
- **Subscription:** `subscription_tier`, `subscription_status`, `trial_ends_at`, Stripe IDs
- **Verification:** `verification_status`, `verification_notes`, `verified_at`
- **Ownership:** `owner_user_id`

**Validation Rules:**
- `handle` must be unique, lowercase, alphanumeric + hyphens only
- `handle` cannot be reserved words: `admin`, `api`, `app`, `dashboard`, `cp`, `search`, `login`, `register`, `terms`, `privacy`

#### 1.3 Business_User Pivot Table

**File:** `database/migrations/YYYY_MM_DD_create_business_user_table.php`
**Purpose:** Many-to-many relationship between users and businesses (for staff access)

```php
Schema::create('business_user', function (Blueprint $table) {
    $table->id();
    $table->foreignId('business_id')->constrained()->onDelete('cascade');
    $table->foreignId('user_id')->constrained()->onDelete('cascade');

    $table->enum('role', ['owner', 'admin', 'staff'])->default('staff');
    // owner: Full access, billing, can delete business
    // admin: Full access except billing/deletion
    // staff: View calendar, manage own appointments only

    $table->boolean('is_active')->default(true);
    $table->timestamp('invited_at')->nullable();
    $table->timestamp('accepted_at')->nullable();

    $table->timestamps();

    $table->unique(['business_id', 'user_id']);
});
```

**Use Cases:**
- Owner invites staff to access dashboard
- Staff can manage appointments assigned to them
- Admin can manage all business settings except billing

### Tasks for Feature 1

#### Task 1.1: Create Businesses Migration
- [ ] Create migration file: `create_businesses_table.php`
- [ ] Define all columns as specified above
- [ ] Add indexes on `handle`, `owner_user_id`, `subscription_tier`
- [ ] Add unique constraint on `handle`

#### Task 1.2: Update Users Table
- [ ] Create migration: `add_role_to_users_table.php`
- [ ] Add `role` enum column if not exists
- [ ] Add index on `role`

#### Task 1.3: Create Business_User Pivot Migration
- [ ] Create migration: `create_business_user_table.php`
- [ ] Define pivot structure with role
- [ ] Add unique constraint on `business_id` + `user_id` pair

#### Task 1.4: Create Business Model
- [ ] Create `app/Models/Business.php`
- [ ] Define fillable/guarded properties
- [ ] Add casts: `settings` => `array`, `verified_at` => `datetime`, `trial_ends_at` => `datetime`
- [ ] Define relationships:
  - `belongsTo(User::class, 'owner_user_id')`
  - `belongsToMany(User::class)->using(BusinessUser::class)` (staff)
  - `hasMany(Location::class)`
  - `hasMany(Service::class)`
  - `hasMany(Booking::class)`
- [ ] Add scopes: `verified()`, `active()`, `onTrial()`, `tier(string $tier)`
- [ ] Add methods: `isOwner(User $user)`, `hasStaff(User $user)`, `canAccess(User $user)`

#### Task 1.5: Update User Model
- [ ] Add relationship: `ownedBusinesses()` - businesses where user is owner
- [ ] Add relationship: `businesses()` - all businesses user has access to (via pivot)
- [ ] Add method: `hasAccessToBusiness(Business $business)`

#### Task 1.6: Create Handle Validation Rule
- [ ] Create `app/Rules/ValidHandle.php` validation rule
- [ ] Check format: lowercase, alphanumeric + hyphens, 3-30 chars
- [ ] Check against reserved words list
- [ ] Check uniqueness in businesses table
- [ ] Return helpful error messages with suggestions

#### Task 1.7: Create Seeders (Development Data)
- [ ] Create `BusinessSeeder.php`
- [ ] Create 5 demo businesses with realistic data
- [ ] Assign to different users (mix of sole traders and teams)
- [ ] Mix of tiers: 2 free, 2 solo, 1 salon
- [ ] Mix of verification statuses

---

## Feature 2: Handle & URL System

### Purpose

Provide branded, memorable URLs for businesses while maintaining stability through canonical URLs.

### URL Structure

```
Marketing URL:  bertie.co.uk/@muddy-paws
                bertie.co.uk/@muddy-paws/fulham

Canonical URL:  bertie.co.uk/p/123-muddy-paws-grooming
                (Always works, even if handle changes)
```

### Database Table

#### 2.1 Handle_Changes Table

**File:** `database/migrations/YYYY_MM_DD_create_handle_changes_table.php`
**Purpose:** Audit trail of handle changes, enables 301 redirects

```php
Schema::create('handle_changes', function (Blueprint $table) {
    $table->id();
    $table->foreignId('business_id')->constrained()->onDelete('cascade');
    $table->string('old_handle');
    $table->string('new_handle');
    $table->foreignId('changed_by_user_id')->constrained('users');
    $table->timestamp('changed_at');

    $table->index(['old_handle', 'created_at']);
});
```

**Use Case:**
- Business changes handle from `@dog-spa` to `@premium-dog-spa`
- Old URLs redirect: `/@dog-spa` → 301 redirect → `/@premium-dog-spa`
- Canonical URL never changes: `/p/123-dog-spa` always works

### Tasks for Feature 2

#### Task 2.1: Create Handle_Changes Migration
- [ ] Create migration: `create_handle_changes_table.php`
- [ ] Define audit trail structure
- [ ] Add index on `old_handle` for redirect lookups

#### Task 2.2: Create HandleChange Model
- [ ] Create `app/Models/HandleChange.php`
- [ ] Define relationship to Business
- [ ] Add scope: `forHandle(string $handle)`

#### Task 2.3: Implement Handle Change Logic
- [ ] Create `app/Services/HandleService.php`
- [ ] Method: `changeHandle(Business $business, string $newHandle)`
  - Check rate limit (max 1 change per 30 days)
  - Validate new handle
  - Create audit record in handle_changes
  - Update business handle
- [ ] Method: `suggestAlternatives(string $desiredHandle)` - return 5 available alternatives

#### Task 2.4: Implement Redirect Middleware
- [ ] Create `app/Http/Middleware/HandleRedirect.php`
- [ ] Check if handle exists in handle_changes (old handles)
- [ ] If found, 301 redirect to new handle
- [ ] If not found, continue to 404

#### Task 2.5: Canonical URL Routes
- [ ] Add route: `/p/{id}-{slug}` → `BusinessController@showCanonical`
- [ ] Add route: `/@{handle}` → Check if current, else redirect, then `BusinessController@show`
- [ ] Add route: `/@{handle}/{location}` → `LocationController@show`

---

## Feature 3: Location Management

### Purpose

Businesses can operate from salons, provide mobile services, or both. Each location has its own booking calendar.

### Location Types
- **Salon** - Fixed address (e.g., "123 High Street, Fulham")
- **Mobile** - Service radius from base address (e.g., "Within 10 miles of SW6")
- **Home-based** - Groomer's home address (treated like salon)

### Database Tables

#### 3.1 Locations Table

**File:** `database/migrations/YYYY_MM_DD_create_locations_table.php`

```php
Schema::create('locations', function (Blueprint $table) {
    $table->id();
    $table->foreignId('business_id')->constrained()->onDelete('cascade');

    // Location Identity
    $table->string('name'); // e.g., "Fulham Salon", "Mobile Service - West London"
    $table->string('slug'); // e.g., "fulham", "west-london"
    $table->enum('location_type', ['salon', 'mobile', 'home_based'])->default('salon');

    // Address
    $table->string('address_line_1');
    $table->string('address_line_2')->nullable();
    $table->string('city');
    $table->string('postcode');
    $table->string('county')->nullable();
    $table->decimal('latitude', 10, 8)->nullable();
    $table->decimal('longitude', 11, 8)->nullable();

    // Mobile Service
    $table->boolean('is_mobile')->default(false);
    $table->integer('service_radius_km')->nullable(); // e.g., 10 (within 10km)

    // Contact
    $table->string('phone')->nullable();
    $table->string('email')->nullable();

    // Settings
    $table->json('opening_hours')->nullable(); // {mon: {open: "09:00", close: "17:00"}, ...}
    $table->integer('booking_buffer_minutes')->default(15); // Gap between appointments
    $table->integer('advance_booking_days')->default(60); // Max days ahead to book
    $table->integer('min_notice_hours')->default(24); // Minimum notice to book

    // Status
    $table->boolean('is_primary')->default(false); // Main location for business
    $table->boolean('is_active')->default(true);
    $table->boolean('accepts_bookings')->default(true);

    $table->timestamps();

    // Indexes
    $table->index(['business_id', 'is_active']);
    $table->index(['city', 'postcode']);
    $table->index(['latitude', 'longitude']); // For distance searches
});
```

**Key Fields:**
- **Identity:** Name distinguishes locations ("Fulham Salon" vs "Chelsea Salon")
- **Geolocation:** Lat/lng for distance calculations and map display
- **Mobile Service:** If `is_mobile=true`, show service radius on map
- **Opening Hours:** JSON stores weekly schedule
- **Booking Settings:** Control booking window and gaps

#### 3.2 Service_Areas Table (for mobile services)

**File:** `database/migrations/YYYY_MM_DD_create_service_areas_table.php`
**Purpose:** Define specific areas a mobile groomer serves

```php
Schema::create('service_areas', function (Blueprint $table) {
    $table->id();
    $table->foreignId('location_id')->constrained()->onDelete('cascade');

    $table->string('area_name'); // e.g., "Fulham", "Chelsea", "Kensington"
    $table->string('postcode_prefix'); // e.g., "SW6", "SW10" (for filtering)

    $table->timestamps();

    $table->index(['location_id', 'postcode_prefix']);
});
```

**Use Case:**
- Mobile groomer serves specific neighborhoods
- Users search by postcode → match against service_areas
- Display "Serves your area" badge on listing

### Tasks for Feature 3

#### Task 3.1: Create Locations Migration
- [ ] Create migration: `create_locations_table.php`
- [ ] Define all columns as specified
- [ ] Add indexes on city, postcode, coordinates
- [ ] Add constraint: Only one `is_primary=true` per business

#### Task 3.2: Create Service_Areas Migration
- [ ] Create migration: `create_service_areas_table.php`
- [ ] Define area and postcode structure
- [ ] Add index on `postcode_prefix` for search

#### Task 3.3: Create Location Model
- [ ] Create `app/Models/Location.php`
- [ ] Define fillable fields
- [ ] Add casts: `opening_hours` => `array`
- [ ] Define relationships:
  - `belongsTo(Business::class)`
  - `hasMany(Booking::class)`
  - `hasMany(ServiceArea::class)`
  - `hasMany(Service::class)` (if services vary by location)
- [ ] Add scopes: `active()`, `acceptingBookings()`, `primary()`, `mobile()`
- [ ] Add methods:
  - `isWithinServiceRadius(float $lat, float $lng)` - Check if coords within radius
  - `servesPostcode(string $postcode)` - Check service areas
  - `getDistanceFrom(float $lat, float $lng)` - Haversine formula

#### Task 3.4: Create ServiceArea Model
- [ ] Create `app/Models/ServiceArea.php`
- [ ] Define relationship to Location
- [ ] Add scope: `forPostcode(string $postcode)`

#### Task 3.5: Geocoding Integration
- [ ] Install geocoding package (e.g., spatie/geocoder)
- [ ] Create `app/Services/GeocodingService.php`
- [ ] Method: `geocode(string $address)` - Returns lat/lng
- [ ] Method: `reverseGeocode(float $lat, float $lng)` - Returns address
- [ ] Cache geocoded results (avoid API spam)

#### Task 3.6: Location Seeders
- [ ] Seed 10-15 demo locations
- [ ] Mix of salon and mobile
- [ ] Distribute across multiple cities
- [ ] Add realistic service areas for mobile locations

---

## Feature 4: Service Catalog

### Purpose

Define grooming services offered with pricing and duration. Services can be location-specific.

### Pricing Models
- **Fixed** - "Full Groom: £45"
- **From** - "Full Groom: From £35" (varies by breed size)
- **Call for Quote** - "Show Preparation: Price on request"

### Database Table

#### 4.1 Services Table

**File:** `database/migrations/YYYY_MM_DD_create_services_table.php`

```php
Schema::create('services', function (Blueprint $table) {
    $table->id();
    $table->foreignId('business_id')->constrained()->onDelete('cascade');
    $table->foreignId('location_id')->nullable()->constrained()->onDelete('cascade');
    // If location_id is null, service applies to all locations

    // Service Details
    $table->string('name'); // e.g., "Full Groom", "Bath & Brush", "Nail Trim"
    $table->text('description')->nullable();
    $table->integer('duration_minutes'); // e.g., 90

    // Pricing
    $table->decimal('price', 8, 2)->nullable(); // e.g., 45.00
    $table->enum('price_type', ['fixed', 'from', 'call'])->default('fixed');
    // fixed: Exact price
    // from: Starting price (e.g., small dogs £35, large dogs £55)
    // call: Contact for quote

    // Display
    $table->integer('display_order')->default(0); // For sorting on listing page
    $table->boolean('is_active')->default(true);
    $table->boolean('is_featured')->default(false); // Highlight on listing

    $table->timestamps();

    $table->index(['business_id', 'is_active', 'display_order']);
});
```

**Use Cases:**
- Business offers "Full Groom" at all locations
- Business offers "Show Preparation" only at main salon (location-specific)
- Customers see services sorted by `display_order`

### Tasks for Feature 4

#### Task 4.1: Create Services Migration
- [ ] Create migration: `create_services_table.php`
- [ ] Define service structure with flexible pricing
- [ ] Add index on `business_id`, `is_active`, `display_order`

#### Task 4.2: Create Service Model
- [ ] Create `app/Models/Service.php`
- [ ] Define fillable fields
- [ ] Add casts: `price` => `decimal:2`
- [ ] Define relationships:
  - `belongsTo(Business::class)`
  - `belongsTo(Location::class)->nullable()`
  - `hasMany(Booking::class)`
- [ ] Add scopes: `active()`, `forLocation(Location $location)`, `featured()`
- [ ] Add methods:
  - `getFormattedPrice()` - Returns "£45.00" or "From £35" or "Price on request"
  - `isAvailableAtLocation(Location $location)` - Check if service offered there

#### Task 4.3: Service Seeders
- [ ] Seed common services:
  - Full Groom (£40-60, 90-120 mins)
  - Bath & Brush (£30-40, 60 mins)
  - Nail Trim (£10-15, 15 mins)
  - Teeth Cleaning (£20-30, 30 mins)
  - Puppy Introduction (£25-35, 45 mins)
- [ ] Assign to demo businesses with variety

---

## Feature 5: Booking System

### Purpose

Complete appointment management from customer booking through completion, including payment and status tracking.

### Booking Lifecycle

```
pending → confirmed → completed
              ↓
           cancelled
           no_show
```

### Database Table

#### 5.1 Bookings Table

**File:** `database/migrations/YYYY_MM_DD_create_bookings_table.php`

```php
Schema::create('bookings', function (Blueprint $table) {
    $table->id();

    // Relationships
    $table->foreignId('business_id')->constrained()->onDelete('cascade');
    $table->foreignId('location_id')->constrained()->onDelete('cascade');
    $table->foreignId('service_id')->constrained()->onDelete('cascade');
    $table->foreignId('customer_id')->constrained('users')->onDelete('cascade');
    $table->foreignId('staff_member_id')->nullable()->constrained('users')->onDelete('set null');
    // If null, "first available" or owner handles it

    // Appointment Details
    $table->dateTime('appointment_datetime');
    $table->integer('duration_minutes');
    $table->enum('status', [
        'pending',      // Just created, awaiting confirmation
        'confirmed',    // Pro accepted/auto-confirmed
        'completed',    // Service performed
        'cancelled',    // Cancelled by either party
        'no_show'       // Customer didn't show up
    ])->default('pending');

    // Pricing
    $table->decimal('price', 8, 2); // Agreed price at time of booking
    $table->decimal('deposit_amount', 8, 2)->default(0);
    $table->boolean('deposit_paid')->default(false);
    $table->enum('payment_status', ['pending', 'deposit_paid', 'paid', 'refunded'])->default('pending');
    $table->string('payment_intent_id')->nullable(); // Stripe Payment Intent ID

    // Notes
    $table->text('customer_notes')->nullable(); // "My dog is nervous around clippers"
    $table->text('pro_notes')->nullable(); // "Use lavender shampoo, remember treats"

    // Reminders
    $table->timestamp('reminder_sent_at')->nullable();
    $table->timestamp('reminder_2hr_sent_at')->nullable();

    // Cancellation
    $table->foreignId('cancelled_by_user_id')->nullable()->constrained('users');
    $table->timestamp('cancelled_at')->nullable();
    $table->text('cancellation_reason')->nullable();

    $table->timestamps();

    // Indexes
    $table->index(['location_id', 'appointment_datetime']);
    $table->index(['staff_member_id', 'appointment_datetime']);
    $table->index(['customer_id', 'appointment_datetime']);
    $table->index('status');
});
```

**Key Fields:**
- **Appointment Details:** datetime, duration determine calendar blocking
- **Status:** Controls what actions are available
- **Payment:** Track deposits and full payments via Stripe
- **Notes:** Communication between customer and pro
- **Reminders:** Track when automated reminders sent

**Business Rules:**
- Cannot double-book same staff member
- Cannot book during blocked times
- Free tier: No bookings allowed (listing only)
- Solo/Salon tier: Full booking functionality

### Tasks for Feature 5

#### Task 5.1: Create Bookings Migration
- [ ] Create migration: `create_bookings_table.php`
- [ ] Define complete booking lifecycle structure
- [ ] Add indexes on location, staff, customer, datetime
- [ ] Add composite index on `location_id` + `appointment_datetime` for conflict checking

#### Task 5.2: Create Booking Model
- [ ] Create `app/Models/Booking.php`
- [ ] Define fillable fields
- [ ] Add casts:
  - `appointment_datetime` => `datetime`
  - `reminder_sent_at` => `datetime`
  - `cancelled_at` => `datetime`
  - `price` => `decimal:2`
  - `deposit_amount` => `decimal:2`
- [ ] Define relationships:
  - `belongsTo(Business::class)`
  - `belongsTo(Location::class)`
  - `belongsTo(Service::class)`
  - `belongsTo(User::class, 'customer_id')`
  - `belongsTo(User::class, 'staff_member_id')->nullable()`
  - `belongsTo(User::class, 'cancelled_by_user_id')->nullable()`
- [ ] Add scopes:
  - `upcoming()` - appointment_datetime > now
  - `past()`
  - `status(string $status)`
  - `forStaff(User $staff)`
  - `forCustomer(User $customer)`
  - `needsReminder()` - appointment within 24hrs, reminder not sent
- [ ] Add methods:
  - `canBeCancelled()` - Check cancellation policy (24hr notice)
  - `cancel(User $user, string $reason)`
  - `markAsCompleted()`
  - `markAsNoShow()`
  - `sendReminder()`

#### Task 5.3: Booking Conflict Detection
- [ ] Create `app/Services/BookingService.php`
- [ ] Method: `checkAvailability(Location $location, DateTime $start, int $duration, ?User $staff = null)`
  - Check no overlapping bookings for location/staff
  - Check against availability blocks
  - Check against opening hours
  - Return true/false + reason
- [ ] Method: `createBooking(array $data)`
  - Validate availability
  - Create booking record
  - Send confirmation email
  - Create calendar event (future)

#### Task 5.4: Booking Seeders
- [ ] Seed 50-100 demo bookings
- [ ] Mix of past and future
- [ ] Mix of statuses (mostly confirmed, some completed, few cancelled)
- [ ] Ensure realistic distribution across days/times
- [ ] Some with deposits paid, some without

---

## Feature 6: Customer/CRM System

### Purpose

Businesses track their customers, pet profiles, and appointment history. Each business has its own isolated customer database.

### Database Table

#### 6.1 Customers Table

**File:** `database/migrations/YYYY_MM_DD_create_customers_table.php`

```php
Schema::create('customers', function (Blueprint $table) {
    $table->id();
    $table->foreignId('business_id')->constrained()->onDelete('cascade');
    $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
    // If user_id is null, customer hasn't registered yet (phone/email booking)

    // Customer Details
    $table->string('name');
    $table->string('email')->nullable();
    $table->string('phone')->nullable();
    $table->text('address')->nullable();

    // Pet Profile
    $table->string('pet_name'); // e.g., "Buddy"
    $table->string('pet_breed')->nullable(); // e.g., "Golden Retriever"
    $table->enum('pet_size', ['small', 'medium', 'large'])->nullable();
    $table->date('pet_birthday')->nullable();
    $table->text('pet_notes')->nullable(); // "Nervous around dryers, allergic to lavender"

    // CRM Fields
    $table->integer('loyalty_points')->default(0);
    $table->integer('total_bookings')->default(0);
    $table->decimal('total_spent', 10, 2)->default(0);
    $table->timestamp('last_visit')->nullable();
    $table->timestamp('birthday')->nullable(); // Customer birthday for marketing

    // Status
    $table->boolean('is_active')->default(true);
    $table->json('tags')->nullable(); // ["VIP", "Regular", "New"]

    $table->timestamps();

    // Indexes
    $table->index(['business_id', 'is_active']);
    $table->index(['business_id', 'email']);
    $table->index(['business_id', 'phone']);
    $table->unique(['business_id', 'email']); // Email unique per business
});
```

**Key Design:**
- **Business Isolation:** Each business has separate customer records (same person can be customer of multiple businesses)
- **User Linking:** Optional link to registered user account
- **Pet Profile:** One primary pet per customer (future: multi-pet support)
- **CRM Data:** Auto-calculated loyalty points, total spent, last visit

### Tasks for Feature 6

#### Task 6.1: Create Customers Migration
- [ ] Create migration: `create_customers_table.php`
- [ ] Define customer and pet profile structure
- [ ] Add indexes on `business_id`, `email`, `phone`
- [ ] Add unique constraint on `business_id` + `email`

#### Task 6.2: Create Customer Model
- [ ] Create `app/Models/Customer.php`
- [ ] Define fillable fields
- [ ] Add casts:
  - `pet_birthday` => `date`
  - `birthday` => `date`
  - `last_visit` => `datetime`
  - `tags` => `array`
  - `total_spent` => `decimal:2`
- [ ] Define relationships:
  - `belongsTo(Business::class)`
  - `belongsTo(User::class)->nullable()`
  - `hasMany(Booking::class, 'customer_id')`
- [ ] Add scopes:
  - `active()`
  - `hasTag(string $tag)`
  - `vip()` - total_spent > threshold
- [ ] Add methods:
  - `incrementLoyaltyPoints(int $amount)`
  - `getPetAge()` - Calculate from pet_birthday
  - `getNextFreeCut()` - If loyalty program (10th cut free)

#### Task 6.3: Customer Auto-Update Logic
- [ ] Create observer: `CustomerObserver`
- [ ] When booking completes:
  - Increment `total_bookings`
  - Add booking price to `total_spent`
  - Update `last_visit`
  - Award loyalty points
- [ ] Method: `updateFromBooking(Booking $booking)`

#### Task 6.4: Customer Seeders
- [ ] Seed 100-200 demo customers
- [ ] Distribute across businesses
- [ ] Realistic names, emails, phone numbers
- [ ] Variety of pet breeds and sizes
- [ ] Some with loyalty points, some new

---

## Feature 7: Staff Management

### Purpose

Salon tier businesses can add staff members with individual calendars and commission tracking.

### Database Table

#### 7.1 Staff_Members Table

**File:** `database/migrations/YYYY_MM_DD_create_staff_members_table.php`

```php
Schema::create('staff_members', function (Blueprint $table) {
    $table->id();
    $table->foreignId('business_id')->constrained()->onDelete('cascade');
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    // Staff member must have user account

    // Staff Details
    $table->string('display_name'); // Public name on booking page
    $table->text('bio')->nullable();
    $table->string('photo_url')->nullable();

    // Employment
    $table->enum('role', ['groomer', 'assistant', 'receptionist'])->default('groomer');
    $table->decimal('commission_rate', 5, 2)->default(0); // e.g., 40.00 for 40%
    $table->string('calendar_color', 7)->default('#6B7280'); // Hex color for calendar

    // Availability
    $table->json('working_locations')->nullable(); // Array of location IDs they work at
    $table->boolean('accepts_online_bookings')->default(true);

    // Status
    $table->boolean('is_active')->default(true);
    $table->timestamp('employed_since')->nullable();
    $table->timestamp('left_at')->nullable();

    $table->timestamps();

    $table->index(['business_id', 'is_active']);
    $table->unique(['business_id', 'user_id']); // User can only be staff once per business
});
```

**Salon Tier Features:**
- Up to 5 staff members
- Individual calendars with color coding
- Commission tracking per booking
- Customers can choose preferred staff

### Tasks for Feature 7

#### Task 7.1: Create Staff_Members Migration
- [ ] Create migration: `create_staff_members_table.php`
- [ ] Define staff profile and employment structure
- [ ] Add indexes on `business_id`, `is_active`
- [ ] Add unique constraint on `business_id` + `user_id`

#### Task 7.2: Create StaffMember Model
- [ ] Create `app/Models/StaffMember.php`
- [ ] Define fillable fields
- [ ] Add casts:
  - `working_locations` => `array`
  - `commission_rate` => `decimal:2`
  - `employed_since` => `datetime`
  - `left_at` => `datetime`
- [ ] Define relationships:
  - `belongsTo(Business::class)`
  - `belongsTo(User::class)`
  - `hasMany(Booking::class, 'staff_member_id')`
- [ ] Add scopes:
  - `active()`
  - `acceptingBookings()`
  - `worksAtLocation(Location $location)`
- [ ] Add methods:
  - `getEarningsForPeriod(Carbon $start, Carbon $end)` - Calculate commission
  - `getBookingCountForPeriod(Carbon $start, Carbon $end)`

#### Task 7.3: Tier Validation
- [ ] Add business scope check: `canAddStaff()`
  - Free tier: Cannot add staff
  - Solo tier: Cannot add staff
  - Salon tier: Up to 5 staff members
- [ ] Throw exception if limit exceeded

#### Task 7.4: Staff Seeders
- [ ] Seed staff members for Salon tier businesses
- [ ] 2-4 staff per salon business
- [ ] Realistic commission rates (30-50%)
- [ ] Different calendar colors

---

## Feature 8: Availability & Scheduling

### Purpose

Define when businesses accept bookings through recurring schedules and one-off blocks.

### Database Table

#### 8.1 Availability_Blocks Table

**File:** `database/migrations/YYYY_MM_DD_create_availability_blocks_table.php`

```php
Schema::create('availability_blocks', function (Blueprint $table) {
    $table->id();
    $table->foreignId('business_id')->constrained()->onDelete('cascade');
    $table->foreignId('location_id')->nullable()->constrained()->onDelete('cascade');
    $table->foreignId('staff_member_id')->nullable()->constrained('users')->onDelete('cascade');
    // If location_id null: applies to all locations
    // If staff_member_id null: applies to all staff (or owner)

    // Recurring Schedule
    $table->integer('day_of_week')->nullable(); // 0=Sunday, 6=Saturday, null=one-off
    $table->time('start_time'); // e.g., "09:00"
    $table->time('end_time');   // e.g., "17:00"

    // One-off Date (overrides recurring)
    $table->date('specific_date')->nullable(); // e.g., "2024-12-25" (Christmas)

    // Block Type
    $table->enum('block_type', [
        'available',    // Working hours (green)
        'break',        // Lunch break (yellow)
        'blocked',      // Personal time off (red)
        'holiday'       // Public/annual leave (red)
    ])->default('available');

    $table->boolean('repeat_weekly')->default(true); // For recurring blocks
    $table->text('notes')->nullable();

    $table->timestamps();

    $table->index(['location_id', 'day_of_week']);
    $table->index(['staff_member_id', 'day_of_week']);
    $table->index(['specific_date']);
});
```

**Use Cases:**
- **Regular Hours:** Monday-Friday 9am-5pm (recurring)
- **Lunch Break:** Daily 1pm-2pm (recurring)
- **Holiday:** December 25th blocked (one-off)
- **Staff PTO:** Sarah unavailable June 10-17 (one-off blocks)

**Conflict Resolution:**
- Specific dates override recurring schedules
- Blocked time prevents bookings
- Available time allows bookings

### Tasks for Feature 8

#### Task 8.1: Create Availability_Blocks Migration
- [ ] Create migration: `create_availability_blocks_table.php`
- [ ] Define recurring and one-off schedule structure
- [ ] Add indexes on location, staff, `day_of_week`, `specific_date`

#### Task 8.2: Create AvailabilityBlock Model
- [ ] Create `app/Models/AvailabilityBlock.php`
- [ ] Define fillable fields
- [ ] Add casts:
  - `start_time` => `datetime:H:i`
  - `end_time` => `datetime:H:i`
  - `specific_date` => `date`
- [ ] Define relationships:
  - `belongsTo(Business::class)`
  - `belongsTo(Location::class)->nullable()`
  - `belongsTo(User::class, 'staff_member_id')->nullable()`
- [ ] Add scopes:
  - `forDate(Carbon $date)`
  - `forDayOfWeek(int $day)`
  - `available()`
  - `blocked()`
- [ ] Add methods:
  - `isActiveOn(Carbon $datetime)` - Check if block applies
  - `conflictsWith(AvailabilityBlock $other)`

#### Task 8.3: Availability Calculation Service
- [ ] Create `app/Services/AvailabilityService.php`
- [ ] Method: `getAvailableSlots(Location $location, Carbon $date, ?User $staff = null)`
  - Load all availability blocks for date
  - Load all bookings for date
  - Calculate free time slots (e.g., 9am-5pm minus bookings minus breaks)
  - Return array of available slots: `[{time: "10:00", duration: 60}, ...]`
- [ ] Method: `isTimeSlotAvailable(Location $location, Carbon $datetime, int $duration, ?User $staff = null)`

#### Task 8.4: Default Availability Seeders
- [ ] Seed default hours for businesses:
  - Mon-Fri: 9am-5pm (available)
  - Sat: 9am-1pm (available)
  - Sun: Closed (blocked)
  - Daily: 1pm-2pm lunch (break)
- [ ] Seed some one-off blocks (holidays, vacation)

---

## Feature 9: Review & Rating System

### Purpose

Customers leave verified reviews, businesses respond, builds trust and SEO.

### Database Table

#### 9.1 Reviews Table

**File:** `database/migrations/YYYY_MM_DD_create_reviews_table.php`

```php
Schema::create('reviews', function (Blueprint $table) {
    $table->id();
    $table->foreignId('business_id')->constrained()->onDelete('cascade');
    $table->foreignId('booking_id')->nullable()->constrained()->onDelete('set null');
    // If booking_id present, review is verified (from real booking)
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    // Customer who left review

    // Review Content
    $table->integer('rating'); // 1-5 stars
    $table->text('review_text')->nullable();
    $table->json('photos')->nullable(); // Array of photo URLs

    // Verification
    $table->boolean('is_verified')->default(false); // From actual booking?
    $table->boolean('is_published')->default(true);

    // Response
    $table->text('response_text')->nullable(); // Business owner response
    $table->foreignId('responded_by_user_id')->nullable()->constrained('users');
    $table->timestamp('responded_at')->nullable();

    // Moderation
    $table->boolean('is_flagged')->default(false);
    $table->text('flag_reason')->nullable();

    $table->timestamps();

    $table->index(['business_id', 'is_published', 'created_at']);
    $table->index(['user_id', 'created_at']);
});
```

**Review Flow:**
1. Customer completes booking
2. Automated email sent 24hrs later: "How was your experience?"
3. Customer clicks link → review form (pre-filled booking details)
4. Review submitted with `booking_id` → `is_verified = true`
5. Business owner gets notification
6. Owner can respond to review

### Tasks for Feature 9

#### Task 9.1: Create Reviews Migration
- [ ] Create migration: `create_reviews_table.php`
- [ ] Define review structure with verification
- [ ] Add indexes on `business_id`, `user_id`, `created_at`

#### Task 9.2: Create Review Model
- [ ] Create `app/Models/Review.php`
- [ ] Define fillable fields
- [ ] Add casts:
  - `photos` => `array`
  - `responded_at` => `datetime`
- [ ] Define relationships:
  - `belongsTo(Business::class)`
  - `belongsTo(Booking::class)->nullable()`
  - `belongsTo(User::class)` (reviewer)
  - `belongsTo(User::class, 'responded_by_user_id')->nullable()`
- [ ] Add scopes:
  - `published()`
  - `verified()`
  - `rating(int $stars)` - e.g., `rating(5)` for 5-star reviews
- [ ] Add methods:
  - `respond(string $text, User $responder)`
  - `flag(string $reason)`

#### Task 9.3: Review Aggregation
- [ ] Add to Business model:
  - `getAverageRating()` - Cached calculation
  - `getReviewCount()`
  - `getRatingBreakdown()` - `[5 => 45, 4 => 8, 3 => 2, 2 => 1, 1 => 0]`
- [ ] Create scheduled job to update cached ratings daily

#### Task 9.4: Review Seeders
- [ ] Seed 50-100 reviews across businesses
- [ ] Mix of ratings (mostly 4-5 stars, some 3, few 1-2)
- [ ] 80% verified (from bookings)
- [ ] Some with responses from business
- [ ] Realistic review text

---

## Feature 10: Payment & Subscription Tracking

### Purpose

Track subscription status and booking payments via Stripe.

### Database Tables

#### 10.1 Subscriptions (Laravel Cashier)

**File:** Already handled by Cashier migration
**Action:** Run Cashier migrations

```bash
php artisan vendor:publish --tag="cashier-migrations"
php artisan migrate
```

This creates:
- `subscriptions` table
- `subscription_items` table

#### 10.2 Transactions Table

**File:** `database/migrations/YYYY_MM_DD_create_transactions_table.php`
**Purpose:** Log all financial transactions for accounting

```php
Schema::create('transactions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('business_id')->constrained()->onDelete('cascade');
    $table->foreignId('booking_id')->nullable()->constrained()->onDelete('set null');

    // Transaction Details
    $table->enum('type', [
        'subscription',      // Monthly subscription fee
        'booking_payment',   // Customer paid for booking
        'booking_deposit',   // Customer paid deposit
        'platform_fee',      // Our 2.5% cut
        'refund',           // Refund to customer
        'location_addon'    // Additional location fee
    ]);
    $table->decimal('amount', 10, 2);
    $table->string('currency', 3)->default('GBP');

    // Stripe References
    $table->string('stripe_payment_intent_id')->nullable();
    $table->string('stripe_charge_id')->nullable();
    $table->string('stripe_invoice_id')->nullable();

    // Status
    $table->enum('status', ['pending', 'completed', 'failed', 'refunded'])->default('pending');
    $table->text('description')->nullable();

    $table->timestamps();

    $table->index(['business_id', 'type', 'created_at']);
    $table->index('stripe_payment_intent_id');
});
```

### Tasks for Feature 10

#### Task 10.1: Install Laravel Cashier
- [ ] Install Cashier: `composer require laravel/cashier`
- [ ] Publish migrations: `php artisan vendor:publish --tag="cashier-migrations"`
- [ ] Run migrations: `php artisan migrate`

#### Task 10.2: Update Business Model for Cashier
- [ ] Add `Billable` trait to Business model
- [ ] Configure Stripe API keys in `.env`
- [ ] Test subscription creation in Stripe test mode

#### Task 10.3: Create Transactions Migration
- [ ] Create migration: `create_transactions_table.php`
- [ ] Define transaction logging structure
- [ ] Add indexes on business, type, Stripe IDs

#### Task 10.4: Create Transaction Model
- [ ] Create `app/Models/Transaction.php`
- [ ] Define fillable fields
- [ ] Add casts: `amount` => `decimal:2`
- [ ] Define relationships:
  - `belongsTo(Business::class)`
  - `belongsTo(Booking::class)->nullable()`
- [ ] Add scopes:
  - `type(string $type)`
  - `completed()`
  - `forPeriod(Carbon $start, Carbon $end)`

#### Task 10.5: Transaction Logging
- [ ] Create `TransactionLogger` service
- [ ] Method: `logSubscription(Business $business, string $invoiceId, float $amount)`
- [ ] Method: `logBookingPayment(Booking $booking, string $paymentIntentId, float $amount)`
- [ ] Method: `logPlatformFee(Booking $booking, float $amount)`

---

## Feature 11: Communication Logging

### Purpose

Track SMS and email usage for quota management and billing.

### Database Tables

#### 11.1 SMS_Log Table

**File:** `database/migrations/YYYY_MM_DD_create_sms_log_table.php`

```php
Schema::create('sms_log', function (Blueprint $table) {
    $table->id();
    $table->foreignId('business_id')->constrained()->onDelete('cascade');
    $table->foreignId('booking_id')->nullable()->constrained()->onDelete('set null');

    // SMS Details
    $table->string('phone_number');
    $table->enum('message_type', [
        'booking_confirmation',
        'reminder_24hr',
        'reminder_2hr',
        'cancellation',
        'custom'
    ]);
    $table->text('message_body');

    // Delivery
    $table->string('twilio_sid')->nullable();
    $table->enum('status', ['queued', 'sent', 'delivered', 'failed'])->default('queued');
    $table->timestamp('sent_at')->nullable();
    $table->timestamp('delivered_at')->nullable();

    // Billing
    $table->decimal('cost', 8, 4)->default(0); // e.g., 0.0500 for 5p per SMS
    $table->boolean('charged_to_business')->default(true);

    $table->timestamps();

    $table->index(['business_id', 'created_at']);
    $table->index(['booking_id']);
});
```

**Use Cases:**
- Solo tier: 30 SMS/month included, £0.06 per additional
- Salon tier: 100 SMS/month included, £0.05 per additional
- Track usage to enforce quotas
- Bill for overages

#### 11.2 Email_Log Table

**File:** `database/migrations/YYYY_MM_DD_create_email_log_table.php`

```php
Schema::create('email_log', function (Blueprint $table) {
    $table->id();
    $table->foreignId('business_id')->nullable()->constrained()->onDelete('cascade');
    $table->foreignId('booking_id')->nullable()->constrained()->onDelete('set null');

    // Email Details
    $table->string('to_email');
    $table->enum('email_type', [
        'welcome',
        'booking_confirmation',
        'reminder',
        'cancellation',
        'review_request',
        'invoice',
        'custom'
    ]);
    $table->string('subject');

    // Delivery
    $table->string('postmark_message_id')->nullable();
    $table->enum('status', ['queued', 'sent', 'delivered', 'bounced', 'failed'])->default('queued');
    $table->timestamp('sent_at')->nullable();
    $table->timestamp('opened_at')->nullable();
    $table->timestamp('clicked_at')->nullable();

    $table->timestamps();

    $table->index(['business_id', 'created_at']);
});
```

### Tasks for Feature 11

#### Task 11.1: Create SMS_Log Migration
- [ ] Create migration: `create_sms_log_table.php`
- [ ] Define SMS tracking structure
- [ ] Add indexes on business, booking, `created_at`

#### Task 11.2: Create Email_Log Migration
- [ ] Create migration: `create_email_log_table.php`
- [ ] Define email tracking structure
- [ ] Add indexes on business, `created_at`

#### Task 11.3: Create SmsLog Model
- [ ] Create `app/Models/SmsLog.php`
- [ ] Define fillable fields
- [ ] Add casts:
  - `sent_at` => `datetime`
  - `delivered_at` => `datetime`
  - `cost` => `decimal:4`
- [ ] Define relationships:
  - `belongsTo(Business::class)`
  - `belongsTo(Booking::class)->nullable()`
- [ ] Add scopes:
  - `forBusiness(Business $business)`
  - `forPeriod(Carbon $start, Carbon $end)`
  - `delivered()`

#### Task 11.4: Create EmailLog Model
- [ ] Create `app/Models/EmailLog.php`
- [ ] Similar structure to SmsLog

#### Task 11.5: SMS Quota Service
- [ ] Create `app/Services/SmsQuotaService.php`
- [ ] Method: `getRemainingQuota(Business $business, Carbon $month)`
  - Calculate quota based on tier
  - Count SMS sent this month
  - Return remaining
- [ ] Method: `canSendSms(Business $business)` - Check if quota available
- [ ] Method: `calculateOverageCharge(Business $business, Carbon $month)`

---

## Final Checklist

### Migrations Completed
- [ ] Users table (update with role)
- [ ] Businesses table
- [ ] Business_User pivot table
- [ ] Handle_Changes table
- [ ] Locations table
- [ ] Service_Areas table
- [ ] Services table
- [ ] Bookings table
- [ ] Customers table
- [ ] Staff_Members table
- [ ] Availability_Blocks table
- [ ] Reviews table
- [ ] Transactions table
- [ ] SMS_Log table
- [ ] Email_Log table
- [ ] Cashier tables (subscriptions)

### Models Created
- [ ] Business
- [ ] Location
- [ ] ServiceArea
- [ ] Service
- [ ] Booking
- [ ] Customer
- [ ] StaffMember
- [ ] AvailabilityBlock
- [ ] Review
- [ ] Transaction
- [ ] SmsLog
- [ ] EmailLog
- [ ] HandleChange

### Services Created
- [ ] HandleService
- [ ] GeocodingService
- [ ] BookingService
- [ ] AvailabilityService
- [ ] SmsQuotaService
- [ ] TransactionLogger

### Seeders Created
- [ ] BusinessSeeder
- [ ] LocationSeeder
- [ ] ServiceSeeder
- [ ] BookingSeeder
- [ ] CustomerSeeder
- [ ] StaffSeeder
- [ ] AvailabilitySeeder
- [ ] ReviewSeeder

### Testing
- [ ] Run all migrations: `php artisan migrate:fresh`
- [ ] Run all seeders: `php artisan db:seed`
- [ ] Verify relationships work in Tinker
- [ ] Test handle uniqueness validation
- [ ] Test booking conflict detection
- [ ] Test availability calculation
- [ ] Verify data isolation (businesses can't see others' data)