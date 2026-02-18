# Phase 3: Professional Onboarding - Detailed Specification

## High-Level Description

This phase builds the multi-step onboarding flow that transforms a registered user into a business owner on heyBertie. A new professional signs up, completes a guided wizard, and ends up with a business profile ready for verification and public listing.

## Dependencies

### From Phase 2 (must be complete)
- `businesses` table + `Business` model (stores the new business)
- `locations` table + `Location` model (stores primary location)
- `services` table + `Service` model (stores initial services)
- `business_user` pivot table (owner role assignment)
- `handle_changes` table + `HandleChange` model (handle audit trail)
- `HandleService` (handle validation, uniqueness, suggestions)
- `GeocodingService` (address → lat/lng during location step)

### From Phase 0/1 (already complete)
- Laravel Breeze authentication (register/login)
- Inertia + React + shadcn/ui component library
- App layout with sidebar navigation
- Tailwind CSS v4 styling

### Outputs consumed by later phases
- **Phase 4 (Listing Page):** Uses the business, location, and services created here to render the public listing
- **Phase 5 (Dashboard):** Onboarding completion redirects to dashboard; business context set
- **Phase 7 (Payments):** Subscription tier chosen during onboarding drives Stripe checkout in Phase 7
- **Phase 9 (Admin):** Verification submissions created here appear in admin review queue

---

## Core Architecture

### Onboarding State Machine

```
Step 1: Business Type     → What kind of grooming business?
Step 2: Business Details   → Name, description, contact info
Step 3: Handle Selection   → Choose unique @handle
Step 4: Location Setup     → Primary location address + type
Step 5: Services Setup     → Add initial services offered
Step 6: Verification       → Upload photo ID + qualifications
Step 7: Plan Selection     → Choose subscription tier (Free/Solo/Salon)
        ↓
   Review & Submit → Create business → Redirect to dashboard
```

### Onboarding Progress Tracking

Onboarding state is stored server-side on the `businesses` table via a JSON `onboarding` column added in this phase. This allows users to leave and resume onboarding.

```php
// businesses.onboarding JSON structure
{
    "current_step": 3,
    "completed_steps": [1, 2],
    "business_type": "salon",
    "started_at": "2026-02-18T10:00:00Z",
    "completed_at": null
}
```

---

## Features Overview

| # | Feature | Description |
|---|---------|-------------|
| 1 | Onboarding Controller & Routing | Server-side flow control, step validation, progress persistence |
| 2 | Step 1: Business Type | Select grooming business type (salon, mobile, home-based, hybrid) |
| 3 | Step 2: Business Details | Business name, description, contact info, logo upload |
| 4 | Step 3: Handle Selection | Choose unique @handle with real-time validation |
| 5 | Step 4: Location Setup | Primary location with address, geocoding, and type configuration |
| 6 | Step 5: Services Setup | Add initial service catalog with pricing |
| 7 | Step 6: Verification | Upload photo ID and qualifications for admin review |
| 8 | Step 7: Plan Selection | Choose subscription tier with feature comparison |
| 9 | Review & Submit | Summary of all steps, final submission |

---

## Feature 1: Onboarding Controller & Routing

### Purpose

Manage the onboarding wizard server-side: enforce step order, validate each step, persist progress, and handle navigation between steps.

### Routes

```php
// routes/web.php (within auth middleware group)
Route::middleware(['auth', 'verified'])->prefix('onboarding')->name('onboarding.')->group(function () {
    Route::get('/', [OnboardingController::class, 'index'])->name('index');           // Redirect to current step
    Route::get('/step/{step}', [OnboardingController::class, 'show'])->name('step');   // Show step
    Route::post('/step/{step}', [OnboardingController::class, 'store'])->name('store'); // Save step
    Route::get('/review', [OnboardingController::class, 'review'])->name('review');    // Final review
    Route::post('/submit', [OnboardingController::class, 'submit'])->name('submit');   // Create business
});
```

### Database Changes

#### 1.1 Add Onboarding Columns to Businesses Table

**File:** `database/migrations/YYYY_MM_DD_add_onboarding_to_businesses_table.php`

```php
Schema::table('businesses', function (Blueprint $table) {
    $table->json('onboarding')->nullable();           // Onboarding progress state
    $table->boolean('onboarding_completed')->default(false); // Quick flag
});
```

#### 1.2 Create Verification_Documents Table

**File:** `database/migrations/YYYY_MM_DD_create_verification_documents_table.php`

```php
Schema::create('verification_documents', function (Blueprint $table) {
    $table->id();
    $table->foreignId('business_id')->constrained()->onDelete('cascade');

    $table->enum('document_type', [
        'photo_id',           // Government-issued photo ID
        'qualification',      // Grooming qualification certificate
        'insurance',          // Public liability insurance
        'other'
    ]);
    $table->string('file_path');          // Storage path
    $table->string('original_filename');
    $table->string('mime_type');
    $table->integer('file_size');         // Bytes

    // Review
    $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
    $table->text('reviewer_notes')->nullable();
    $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users');
    $table->timestamp('reviewed_at')->nullable();

    $table->timestamps();

    $table->index(['business_id', 'document_type']);
});
```

### Controller Structure

```php
// app/Http/Controllers/OnboardingController.php

class OnboardingController extends Controller
{
    public function __construct(
        private HandleService $handleService,
        private GeocodingService $geocodingService,
    ) {}

    // GET /onboarding → redirect to current step
    public function index(): RedirectResponse

    // GET /onboarding/step/{step} → render step component
    public function show(int $step): Response

    // POST /onboarding/step/{step} → validate & save step data
    public function store(int $step, Request $request): RedirectResponse

    // GET /onboarding/review → show summary
    public function review(): Response

    // POST /onboarding/submit → finalize business creation
    public function submit(): RedirectResponse
}
```

### Tasks for Feature 1

#### Task 1.1: Create Onboarding Migration
- [ ] Create migration: `add_onboarding_to_businesses_table.php`
- [ ] Add `onboarding` JSON column and `onboarding_completed` boolean
- [ ] Update Business model casts: `onboarding` => `array`

#### Task 1.2: Create Verification_Documents Migration
- [ ] Create migration: `create_verification_documents_table.php`
- [ ] Define document storage and review structure
- [ ] Add index on `business_id`, `document_type`

#### Task 1.3: Create VerificationDocument Model
- [ ] Create `app/Models/VerificationDocument.php`
- [ ] Define fillable fields
- [ ] Add casts: `reviewed_at` => `datetime`
- [ ] Define relationships:
  - `belongsTo(Business::class)`
  - `belongsTo(User::class, 'reviewed_by_user_id')->nullable()`
- [ ] Add scopes: `pending()`, `approved()`, `rejected()`, `ofType(string $type)`

#### Task 1.4: Create OnboardingController
- [ ] Create `app/Http/Controllers/OnboardingController.php`
- [ ] Implement `index()` - redirect to current/first incomplete step
- [ ] Implement `show(int $step)` - render Inertia page for each step
- [ ] Implement `store(int $step)` - validate and persist step data
- [ ] Implement `review()` - load all step data for summary
- [ ] Implement `submit()` - finalize business, set `onboarding_completed = true`
- [ ] Add guard: redirect to dashboard if onboarding already completed

#### Task 1.5: Create Form Request Classes
- [ ] `StoreBusinessTypeRequest` (step 1)
- [ ] `StoreBusinessDetailsRequest` (step 2)
- [ ] `StoreHandleRequest` (step 3)
- [ ] `StoreLocationRequest` (step 4)
- [ ] `StoreServicesRequest` (step 5)
- [ ] `StoreVerificationRequest` (step 6)
- [ ] `StorePlanSelectionRequest` (step 7)

#### Task 1.6: Register Onboarding Routes
- [ ] Add onboarding route group in `routes/web.php`
- [ ] Ensure `auth` and `verified` middleware applied
- [ ] Named routes: `onboarding.index`, `onboarding.step`, etc.

#### Task 1.7: Create OnboardingMiddleware
- [ ] Create `app/Http/Middleware/EnsureOnboardingComplete.php`
- [ ] Redirect to onboarding if user has a business with `onboarding_completed = false`
- [ ] Apply to dashboard routes (Phase 5)
- [ ] Skip for onboarding routes themselves

---

## Feature 2: Step 1 - Business Type

### Purpose

User selects what kind of grooming business they run. This determines location setup options in Step 4.

### UI Design

Visual card selection with icons:
- **Salon** - Fixed location grooming salon
- **Mobile** - Travel to customers' homes
- **Home-based** - Groom from your own home
- **Hybrid** - Combination (salon + mobile service)

### Inertia Page

**File:** `resources/js/pages/onboarding/step-1-business-type.tsx`

### Props from Controller

```typescript
interface Step1Props {
    step: number;
    totalSteps: number;
    businessType: string | null; // Pre-filled if returning to step
}
```

### Validation Rules

```php
// StoreBusinessTypeRequest
'business_type' => ['required', 'in:salon,mobile,home_based,hybrid'],
```

### Tasks for Feature 2

#### Task 2.1: Create Step 1 Page Component
- [ ] Create `resources/js/pages/onboarding/step-1-business-type.tsx`
- [ ] Card-based selection UI with icons for each type
- [ ] Highlight selected card
- [ ] Brief description under each option
- [ ] Next button (disabled until selection made)
- [ ] Progress indicator showing step 1 of 7

#### Task 2.2: Create StoreBusinessTypeRequest
- [ ] Create form request with `business_type` validation
- [ ] Rule: `required|in:salon,mobile,home_based,hybrid`

---

## Feature 3: Step 2 - Business Details

### Purpose

Capture core business identity: name, description, contact info, and optional logo.

### Inertia Page

**File:** `resources/js/pages/onboarding/step-2-business-details.tsx`

### Props from Controller

```typescript
interface Step2Props {
    step: number;
    totalSteps: number;
    business: {
        name: string | null;
        description: string | null;
        phone: string | null;
        email: string | null;
        website: string | null;
        logo_url: string | null;
    };
}
```

### Form Fields

| Field | Type | Required | Validation |
|-------|------|----------|------------|
| Business Name | text | Yes | `string, max:255` |
| Description | textarea | No | `nullable, string, max:1000` |
| Phone | tel | No | `nullable, string, regex UK phone` |
| Email | email | No | `nullable, email` |
| Website | url | No | `nullable, url` |
| Logo | file upload | No | `nullable, image, max:2048` |

### Tasks for Feature 3

#### Task 3.1: Create Step 2 Page Component
- [ ] Create `resources/js/pages/onboarding/step-2-business-details.tsx`
- [ ] Form with text inputs for name, description, phone, email, website
- [ ] Logo upload with preview (drag & drop or click)
- [ ] Character counter for description
- [ ] Back/Next navigation
- [ ] Pre-fill fields if returning to step

#### Task 3.2: Create StoreBusinessDetailsRequest
- [ ] Validate all business detail fields
- [ ] UK phone regex validation
- [ ] Image upload validation (max 2MB, jpg/png/webp)

#### Task 3.3: Implement Logo Upload
- [ ] Store logos in `storage/app/public/logos/`
- [ ] Generate unique filename
- [ ] Create thumbnail (200x200) for listing display
- [ ] Return public URL

---

## Feature 4: Step 3 - Handle Selection

### Purpose

User picks their unique @handle for branded URLs. Real-time availability checking with suggestions.

### Inertia Page

**File:** `resources/js/pages/onboarding/step-3-handle.tsx`

### Props from Controller

```typescript
interface Step3Props {
    step: number;
    totalSteps: number;
    handle: string | null;
    suggestedHandles: string[]; // Auto-generated from business name
}
```

### UX Flow

1. Auto-suggest handle from business name (e.g., "Muddy Paws Grooming" → "muddy-paws")
2. User can accept or type custom handle
3. Real-time availability check (debounced API call)
4. Show green check for available, red X for taken
5. If taken, show 5 alternative suggestions

### API Endpoint

```php
// routes/web.php
Route::post('/onboarding/check-handle', [OnboardingController::class, 'checkHandle'])
    ->middleware(['auth', 'throttle:30,1'])
    ->name('onboarding.check-handle');
```

### Validation Rules (via HandleService from Phase 2)

```php
// StoreHandleRequest
'handle' => ['required', 'string', 'min:3', 'max:30', new ValidHandle],
```

### Tasks for Feature 4

#### Task 4.1: Create Step 3 Page Component
- [ ] Create `resources/js/pages/onboarding/step-3-handle.tsx`
- [ ] Text input with `@` prefix display
- [ ] Auto-generate from business name on mount
- [ ] Debounced availability check (300ms) via API
- [ ] Availability indicator (green check / red X)
- [ ] Alternative suggestions when handle is taken
- [ ] Preview of final URL: `bertie.co.uk/@{handle}`
- [ ] Format rules display (lowercase, hyphens, 3-30 chars)

#### Task 4.2: Create Handle Check API Endpoint
- [ ] Add `checkHandle()` method to OnboardingController
- [ ] Use `HandleService::isAvailable(string $handle)` from Phase 2
- [ ] Return JSON: `{ available: bool, suggestions: string[] }`
- [ ] Throttle: 30 requests per minute

#### Task 4.3: Create StoreHandleRequest
- [ ] Use `ValidHandle` rule from Phase 2
- [ ] Ensure handle uniqueness at submission time (race condition guard)

---

## Feature 5: Step 4 - Location Setup

### Purpose

Configure the primary business location. The type of location options shown depends on the business type selected in Step 1.

### Inertia Page

**File:** `resources/js/pages/onboarding/step-4-location.tsx`

### Props from Controller

```typescript
interface Step4Props {
    step: number;
    totalSteps: number;
    businessType: string; // From step 1 - determines which fields shown
    location: {
        name: string | null;
        location_type: string | null;
        address_line_1: string | null;
        address_line_2: string | null;
        city: string | null;
        postcode: string | null;
        county: string | null;
        service_radius_km: number | null;
        phone: string | null;
        email: string | null;
    };
}
```

### Conditional Fields by Business Type

| Business Type | Address Required | Service Radius | Location Type Options |
|---------------|-----------------|----------------|-----------------------|
| Salon | Yes | No | `salon` |
| Mobile | Yes (base) | Yes | `mobile` |
| Home-based | Yes | No | `home_based` |
| Hybrid | Yes | Yes (optional) | `salon`, `mobile` |

### Tasks for Feature 5

#### Task 5.1: Create Step 4 Page Component
- [ ] Create `resources/js/pages/onboarding/step-4-location.tsx`
- [ ] Location name input
- [ ] Address form fields (line 1, line 2, city, postcode, county)
- [ ] UK postcode validation and auto-lookup (optional future enhancement)
- [ ] Conditional service radius slider (for mobile/hybrid)
- [ ] Location type selector (for hybrid businesses)
- [ ] Contact details (phone, email) - pre-fill from business details

#### Task 5.2: Create StoreLocationRequest
- [ ] Validate address fields (address_line_1, city, postcode required)
- [ ] UK postcode regex validation
- [ ] Conditional `service_radius_km` validation (required if mobile)
- [ ] Validate `location_type` matches `business_type` from step 1

#### Task 5.3: Geocoding on Save
- [ ] Call `GeocodingService::geocode()` from Phase 2 when step is saved
- [ ] Store lat/lng on location record
- [ ] Handle geocoding failures gracefully (allow save without coords, retry later)

---

## Feature 6: Step 5 - Services Setup

### Purpose

Add initial services offered by the business. Users can add multiple services with name, duration, and pricing.

### Inertia Page

**File:** `resources/js/pages/onboarding/step-5-services.tsx`

### Props from Controller

```typescript
interface Step5Props {
    step: number;
    totalSteps: number;
    services: Array<{
        name: string;
        description: string | null;
        duration_minutes: number;
        price: number | null;
        price_type: 'fixed' | 'from' | 'call';
    }>;
    suggestedServices: Array<{
        name: string;
        typical_duration: number;
        typical_price: number;
    }>;
}
```

### UX Flow

1. Show pre-populated common grooming services as suggestions (click to add)
2. User can customize name, duration, price for each
3. User can add custom services
4. Minimum 1 service required
5. Drag to reorder (sets `display_order`)

### Suggested Services (Pre-populated)

| Service | Duration | Typical Price |
|---------|----------|---------------|
| Full Groom | 90 min | £45.00 |
| Bath & Brush | 60 min | £30.00 |
| Puppy First Groom | 45 min | £25.00 |
| Nail Trim | 15 min | £10.00 |
| Teeth Cleaning | 30 min | £20.00 |
| De-matting | 60 min | £35.00 |
| Hand Stripping | 120 min | £60.00 |

### Tasks for Feature 6

#### Task 6.1: Create Step 5 Page Component
- [ ] Create `resources/js/pages/onboarding/step-5-services.tsx`
- [ ] Service suggestion cards (click to add to list)
- [ ] Editable service list with name, duration (dropdown), price, price type
- [ ] Add custom service button
- [ ] Remove service button (with confirmation if only 1 left)
- [ ] Drag-to-reorder functionality
- [ ] Minimum 1 service validation (client-side)

#### Task 6.2: Create StoreServicesRequest
- [ ] Validate array of services: `services.*.name`, `services.*.duration_minutes`, etc.
- [ ] At least 1 service required
- [ ] Duration must be positive integer, reasonable range (5-480 minutes)
- [ ] Price validation: required if `price_type` is `fixed` or `from`, nullable if `call`

---

## Feature 7: Step 6 - Verification

### Purpose

Collect identity and qualification documents for admin review. Builds trust with customers and prevents fraudulent listings.

### Inertia Page

**File:** `resources/js/pages/onboarding/step-6-verification.tsx`

### Props from Controller

```typescript
interface Step6Props {
    step: number;
    totalSteps: number;
    documents: Array<{
        id: number;
        document_type: string;
        original_filename: string;
        status: string;
    }>;
    requiredDocuments: string[]; // ['photo_id']
    optionalDocuments: string[]; // ['qualification', 'insurance']
}
```

### Document Requirements

| Document | Required | Description |
|----------|----------|-------------|
| Photo ID | Yes | Government-issued photo ID (passport, driving licence) |
| Grooming Qualification | No | City & Guilds, ICMG, or equivalent certificate |
| Insurance Certificate | No | Public liability insurance document |

### Tasks for Feature 7

#### Task 7.1: Create Step 6 Page Component
- [ ] Create `resources/js/pages/onboarding/step-6-verification.tsx`
- [ ] Document upload zones for each type (drag & drop)
- [ ] File type restrictions (jpg, png, pdf - max 5MB)
- [ ] Upload progress indicator
- [ ] Preview uploaded documents (thumbnail for images, icon for PDFs)
- [ ] Remove/replace uploaded document
- [ ] Explanation of why verification is needed (trust, safety)
- [ ] "Skip for now" option for optional documents
- [ ] Note: business will show as "Verification Pending" until approved

#### Task 7.2: Create StoreVerificationRequest
- [ ] Validate file uploads: `mimes:jpg,jpeg,png,pdf`, `max:5120`
- [ ] Photo ID required, others optional
- [ ] At least 1 document must be uploaded

#### Task 7.3: Implement Document Upload
- [ ] Store in `storage/app/private/verification/{business_id}/`
- [ ] Private disk - not publicly accessible (admin only)
- [ ] Generate unique filename preserving extension
- [ ] Create VerificationDocument record with metadata
- [ ] Queue thumbnail generation for images

---

## Feature 8: Step 7 - Plan Selection

### Purpose

Present subscription tiers with feature comparison. User selects their plan. Actual payment processing happens in Phase 7 (Stripe integration).

### Inertia Page

**File:** `resources/js/pages/onboarding/step-7-plan.tsx`

### Props from Controller

```typescript
interface Step7Props {
    step: number;
    totalSteps: number;
    selectedTier: string | null;
    plans: Array<{
        tier: 'free' | 'solo' | 'salon';
        name: string;
        price: number; // Monthly in GBP
        features: string[];
        highlighted: boolean; // "Most Popular" badge
        cta: string;
    }>;
}
```

### Plan Comparison

| Feature | Free (£0) | Solo (£29/mo) | Salon (£79/mo) |
|---------|-----------|---------------|-----------------|
| Business listing | Yes | Yes | Yes |
| Handle URL (@name) | Yes | Yes | Yes |
| Booking calendar | No | Yes | Yes |
| Online payments | No | Yes | Yes |
| CRM / customer notes | No | Yes | Yes |
| SMS reminders | No | 30/month | 100/month |
| Email reminders | No | Unlimited | Unlimited |
| Staff calendars | No | No | Up to 5 |
| Multiple locations | No | No | Up to 3 |
| Loyalty program | No | No | Yes |
| Analytics | No | Basic | Advanced |
| Priority support | No | No | Yes |

### Tasks for Feature 8

#### Task 8.1: Create Step 7 Page Component
- [ ] Create `resources/js/pages/onboarding/step-7-plan.tsx`
- [ ] Three pricing cards side by side (responsive: stacked on mobile)
- [ ] "Most Popular" badge on Solo tier
- [ ] Feature comparison list with check/cross icons
- [ ] Monthly price display with "per month" label
- [ ] "Start Free" / "Start 14-day Trial" CTAs
- [ ] Selected plan highlighted state
- [ ] Trial period note: "All paid plans include a 14-day free trial"
- [ ] "You can upgrade anytime" reassurance text

#### Task 8.2: Create StorePlanSelectionRequest
- [ ] Validate: `tier` required, `in:free,solo,salon`

---

## Feature 9: Review & Submit

### Purpose

Show a summary of all onboarding data for final review before creating the business.

### Inertia Page

**File:** `resources/js/pages/onboarding/review.tsx`

### Props from Controller

```typescript
interface ReviewProps {
    business: {
        type: string;
        name: string;
        description: string | null;
        handle: string;
        phone: string | null;
        email: string | null;
        website: string | null;
        logo_url: string | null;
    };
    location: {
        name: string;
        location_type: string;
        address: string; // Formatted full address
        service_radius_km: number | null;
    };
    services: Array<{
        name: string;
        duration_minutes: number;
        price: number | null;
        price_type: string;
    }>;
    verification: {
        documents_count: number;
        has_photo_id: boolean;
    };
    plan: {
        tier: string;
        name: string;
        price: number;
    };
}
```

### Tasks for Feature 9

#### Task 9.1: Create Review Page Component
- [ ] Create `resources/js/pages/onboarding/review.tsx`
- [ ] Summary cards for each step with edit links
- [ ] Business details card (name, handle, contact)
- [ ] Location card (address, type, radius)
- [ ] Services list (name, duration, price)
- [ ] Verification status (documents uploaded count)
- [ ] Selected plan card
- [ ] "Create My Business" submit button
- [ ] Terms acceptance checkbox: "I agree to the Terms of Service and Privacy Policy"

#### Task 9.2: Implement Submit Logic
- [ ] OnboardingController `submit()` method
- [ ] Create Business record (or update draft)
- [ ] Create Location record with geocoded coordinates
- [ ] Create Service records with display_order
- [ ] Create business_user pivot entry (owner role)
- [ ] Set `onboarding_completed = true`
- [ ] Update user role from `customer` to `pro`
- [ ] Set `verification_status = 'pending'` on business
- [ ] Set subscription: tier from selection, status = `trial`, trial_ends_at = now + 14 days
- [ ] Redirect to dashboard with success flash message
- [ ] Wrap in database transaction for atomicity

---

## Shared Components

### Onboarding Layout

**File:** `resources/js/layouts/onboarding-layout.tsx`

A dedicated layout for the onboarding flow, separate from the app layout.

- [ ] Clean, distraction-free layout (no sidebar)
- [ ] heyBertie logo top-left
- [ ] Progress bar showing current step / total steps
- [ ] Step labels under progress bar (clickable for completed steps)
- [ ] "Save & Exit" option (persists progress, returns to dashboard)
- [ ] Mobile-responsive

### Onboarding Progress Component

**File:** `resources/js/components/onboarding/progress-bar.tsx`

- [ ] Visual step indicator (numbered circles connected by lines)
- [ ] Current step highlighted
- [ ] Completed steps with checkmark
- [ ] Future steps greyed out
- [ ] Step labels: Type → Details → Handle → Location → Services → Verify → Plan

### Step Navigation Component

**File:** `resources/js/components/onboarding/step-navigation.tsx`

- [ ] Back button (except step 1)
- [ ] Next / Continue button
- [ ] Loading state on submit
- [ ] Keyboard navigation (Enter to proceed)

---

## OnboardingService

**File:** `app/Services/OnboardingService.php`

Centralizes onboarding business logic, keeping the controller thin.

```php
class OnboardingService
{
    // Get or create the draft business for the authenticated user
    public function getDraftBusiness(User $user): ?Business

    // Create a new draft business for onboarding
    public function createDraft(User $user): Business

    // Save data for a specific step
    public function saveStep(Business $business, int $step, array $data): void

    // Get the current step (first incomplete)
    public function getCurrentStep(Business $business): int

    // Check if a step is accessible (all previous steps complete)
    public function canAccessStep(Business $business, int $step): bool

    // Finalize: create all records, mark complete
    public function finalize(Business $business): void

    // Get suggested services based on business type
    public function getSuggestedServices(string $businessType): array

    // Generate handle suggestions from business name
    public function suggestHandles(string $businessName): array
}
```

### Tasks for OnboardingService

#### Task S.1: Create OnboardingService
- [ ] Create `app/Services/OnboardingService.php`
- [ ] Implement draft business management
- [ ] Implement step progress tracking via `onboarding` JSON
- [ ] Implement `finalize()` with database transaction
- [ ] Implement service suggestions helper
- [ ] Implement handle suggestion helper (delegates to HandleService)

---

## Testing

### Feature Tests

#### Onboarding Flow Tests
- [ ] `tests/Feature/Onboarding/OnboardingFlowTest.php`
  - [ ] Test: Unauthenticated user redirected to login
  - [ ] Test: New user starts at step 1
  - [ ] Test: Can save step 1 and progress to step 2
  - [ ] Test: Cannot skip to step 3 without completing step 2
  - [ ] Test: Can navigate back to previous steps
  - [ ] Test: Returning user resumes from last step
  - [ ] Test: Complete flow creates business with all related records
  - [ ] Test: User role updated to `pro` after completion
  - [ ] Test: Completed onboarding redirects to dashboard

#### Handle Validation Tests
- [ ] `tests/Feature/Onboarding/HandleValidationTest.php`
  - [ ] Test: Valid handle accepted
  - [ ] Test: Reserved words rejected
  - [ ] Test: Duplicate handle rejected
  - [ ] Test: Handle format validation (length, characters)
  - [ ] Test: Handle availability API returns correct response
  - [ ] Test: Handle suggestions returned when taken

#### Verification Upload Tests
- [ ] `tests/Feature/Onboarding/VerificationUploadTest.php`
  - [ ] Test: Valid photo ID upload succeeds
  - [ ] Test: Invalid file type rejected
  - [ ] Test: File too large rejected
  - [ ] Test: Files stored in private disk
  - [ ] Test: VerificationDocument record created

#### Step Validation Tests
- [ ] `tests/Feature/Onboarding/StepValidationTest.php`
  - [ ] Test: Step 1 requires business_type
  - [ ] Test: Step 2 requires business name
  - [ ] Test: Step 4 requires address fields
  - [ ] Test: Step 5 requires at least 1 service
  - [ ] Test: Step 7 requires tier selection

### Unit Tests

- [ ] `tests/Unit/Services/OnboardingServiceTest.php`
  - [ ] Test: getDraftBusiness returns null when no draft exists
  - [ ] Test: createDraft creates business with onboarding state
  - [ ] Test: saveStep updates onboarding JSON correctly
  - [ ] Test: getCurrentStep returns correct step
  - [ ] Test: canAccessStep enforces sequential flow
  - [ ] Test: finalize creates all records atomically

---

## Final Checklist

### Migrations
- [ ] `add_onboarding_to_businesses_table`
- [ ] `create_verification_documents_table`

### Models
- [ ] VerificationDocument (new)
- [ ] Business (updated with onboarding casts)

### Controllers
- [ ] OnboardingController

### Services
- [ ] OnboardingService

### Form Requests
- [ ] StoreBusinessTypeRequest
- [ ] StoreBusinessDetailsRequest
- [ ] StoreHandleRequest
- [ ] StoreLocationRequest
- [ ] StoreServicesRequest
- [ ] StoreVerificationRequest
- [ ] StorePlanSelectionRequest

### Middleware
- [ ] EnsureOnboardingComplete

### Inertia Pages
- [ ] `pages/onboarding/step-1-business-type.tsx`
- [ ] `pages/onboarding/step-2-business-details.tsx`
- [ ] `pages/onboarding/step-3-handle.tsx`
- [ ] `pages/onboarding/step-4-location.tsx`
- [ ] `pages/onboarding/step-5-services.tsx`
- [ ] `pages/onboarding/step-6-verification.tsx`
- [ ] `pages/onboarding/step-7-plan.tsx`
- [ ] `pages/onboarding/review.tsx`

### Shared Components
- [ ] `layouts/onboarding-layout.tsx`
- [ ] `components/onboarding/progress-bar.tsx`
- [ ] `components/onboarding/step-navigation.tsx`

### Tests
- [ ] OnboardingFlowTest
- [ ] HandleValidationTest
- [ ] VerificationUploadTest
- [ ] StepValidationTest
- [ ] OnboardingServiceTest