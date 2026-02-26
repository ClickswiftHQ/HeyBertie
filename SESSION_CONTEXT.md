# Session Context

> Overwritten at the end of each session. Provides immediate context for the next conversation.

## Where we left off

**Phase 8a (Stripe Subscription Management) — fully implemented.** Phase 8b planned but not yet started.

---

## Next up: Phase 8b — Post-Onboarding Checkout Redirect & Dashboard Subscription Banner

**Goal**: Redirect paid-tier users to Stripe Checkout immediately after onboarding, and add a contextual subscription banner to the dashboard.

### Step 1: Redirect paid-tier users to Stripe Checkout after onboarding

**File:** `app/Http/Controllers/OnboardingController.php` (line 187-189)

After `$this->onboardingService->finalize($business)`, check if the tier has a `stripe_price_id`. If so, redirect to `subscription.checkout` instead of the dashboard.

```php
$this->onboardingService->finalize($business);
$business->refresh();

if ($business->subscriptionTier->stripe_price_id) {
    return redirect()->route('subscription.checkout', $business->handle);
}

return redirect()->route('business.dashboard', $business->handle)
    ->with('success', 'Your business has been created! Welcome to heyBertie.');
```

- Free tier has `stripe_price_id = null` → goes to dashboard as normal
- Solo/Salon have `stripe_price_id` set → goes to Stripe Checkout
- If user cancels out of Checkout, the existing `subscription.cancelled` route redirects to dashboard with info flash

### Step 2: Add subscription data to shared Inertia props

**File:** `app/Http/Middleware/HandleInertiaRequests.php` (`getCurrentBusiness()`)

Add three fields:

```php
'has_active_subscription' => $business->hasActiveSubscription(),
'on_trial' => $business->onGenericTrial(),
'trial_days_remaining' => $business->trial_ends_at && $business->trial_ends_at->isFuture()
    ? (int) ceil(now()->floatDiffInDays($business->trial_ends_at, false))
    : null,
```

Update the PHPDoc `@return` to include the new keys.

### Step 3: Update TypeScript types

**File:** `resources/js/types/business.ts`

Add to `CurrentBusiness`:

```ts
has_active_subscription: boolean;
on_trial: boolean;
trial_days_remaining: number | null;
```

### Step 4: Create subscription banner component

**New file:** `resources/js/components/dashboard/subscription-banner.tsx`

Reads `currentBusiness` from `usePage().props`. Four states:

| State | Style | Message | CTA |
|-------|-------|---------|-----|
| Active subscriber (not on trial) | Hidden | — | — |
| Free tier | Blue | "Want to accept bookings online?" | None (no pricing page yet) |
| On trial, >3 days left | Blue | "X days left in your free trial" | "Subscribe now" → checkout |
| On trial, ≤3 days left | Amber | "X days left in your free trial" | "Subscribe now" → checkout |
| Trial expired / no subscription | Red | "Your trial has ended" | "Subscribe now" → checkout |

Uses existing `Alert`/`AlertTitle`/`AlertDescription` from `resources/js/components/ui/alert.tsx` and `lucide-react` icons.

### Step 5: Wire banner into dashboard

**File:** `resources/js/pages/dashboard/index.tsx` (line 43)

Add `<SubscriptionBanner />` at the top of the content area, before the stat cards grid.

### Step 6: Tests

**6a. Onboarding redirect tests** — `tests/Feature/Onboarding/OnboardingFlowTest.php`
- Ensure `solo`/`salon` tier seeders include `stripe_price_id`
- Add test: submit with paid tier → redirects to `subscription.checkout`
- Add test: submit with free tier → redirects to `business.dashboard`

**6b. Dashboard subscription props tests** — New file `tests/Feature/Dashboard/DashboardSubscriptionBannerTest.php`
- Trial business → `has_active_subscription: true`, `on_trial: true`, `trial_days_remaining: X`
- Expired trial → `has_active_subscription: false`, `on_trial: false`, `trial_days_remaining: null`
- Free tier → `subscription_tier: 'free'`, `has_active_subscription: false`

### Step 7: Verification

1. `vendor/bin/pint --dirty --format agent`
2. `php artisan test --compact --filter=Onboarding`
3. `php artisan test --compact --filter=DashboardSubscription`

### Critical files

| File | Change |
|------|--------|
| `app/Http/Controllers/OnboardingController.php` | Conditional redirect to Checkout for paid tiers |
| `app/Http/Middleware/HandleInertiaRequests.php` | Add subscription fields to shared props |
| `resources/js/types/business.ts` | Extend `CurrentBusiness` type |
| `resources/js/components/dashboard/subscription-banner.tsx` | New — contextual banner component |
| `resources/js/pages/dashboard/index.tsx` | Render banner above stat cards |
| `tests/Feature/Onboarding/OnboardingFlowTest.php` | Add redirect assertion tests |
| `tests/Feature/Dashboard/DashboardSubscriptionBannerTest.php` | New — test subscription props |

---

## Failing tests (pre-existing)

7 tests fail — all dashboard/E2E related (Vite manifest missing — needs `npm run build`):

| Test file | Failures |
|-----------|----------|
| `tests/Feature/Dashboard/BusinessContextTest.php` | 4 |
| `tests/Feature/Dashboard/DashboardAccessTest.php` | 2 |
| `tests/Feature/E2E/BusinessOwnerJourneyTest.php` | 1 |

---

## Still pending (lower priority)

- **Address & Geocoding rework** — `geocode_cache` + `address_cache` tables, plan at `.claude/plans/glowing-meandering-patterson.md`
- **Phase 7 follow-ups** — Booking confirmation email, customer booking management (view & amend)
