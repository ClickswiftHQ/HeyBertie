# Phase 8: Payment Integration

## Phase 8a: Stripe Subscription Management

### Context

Businesses select a tier during onboarding (Free/Solo/Salon) and get a 14-day trial with no payment. Phase 8a adds Laravel Cashier (Stripe) to handle subscription checkout, billing portal, webhooks, and trial expiry — all on the **Business** model (not User).

**Architecture**: Cashier manages the Stripe lifecycle in its own `subscriptions` table. Our existing `subscription_tier_id` + `subscription_status_id` columns remain the access-control layer. A webhook listener syncs between the two.

### What's Included

1. Laravel Cashier installation with Business as the billable model
2. Stripe Checkout for subscription creation
3. Billing Portal for managing subscriptions
4. Webhook listener to sync Stripe state → our tier/status columns
5. Trial expiry command (daily cron)
6. Database-driven trial days (no more hardcoded 14)

### Key Routes

| Route | Action |
|-------|--------|
| `GET /{handle}/subscription/checkout` | Redirect to Stripe Checkout |
| `GET /{handle}/subscription/success` | Post-checkout success |
| `GET /{handle}/subscription/cancel` | Post-checkout cancellation |
| `GET /{handle}/billing` | Redirect to Stripe Billing Portal |

### Stripe Dashboard Setup (Post-Implementation)

1. Create products/prices in Stripe (Solo monthly, Salon monthly)
2. Copy price IDs into `subscription_tiers.stripe_price_id`
3. Configure Billing Portal (plan switching + cancellation)
4. Set webhook URL: `{APP_URL}/stripe/webhook`
5. Local dev: `stripe listen --forward-to heybertie.test/stripe/webhook`

---

## Phase 8b: Post-Onboarding Checkout Redirect & Dashboard Banner

*(Implemented — see git history for details)*

---

## Phase 8c: Booking Payments (Stripe Connect + Deposits)

### Context

Currently, no payment is collected at booking time — customers pay the business directly on the day. This phase adds:

1. **Stripe Connect (Express)** — each business gets a connected Stripe account so payments can be split automatically
2. **Booking deposits** — customers pay a deposit when booking (configurable per-business)
3. **Platform fee** — HeyBertie takes 2.5% of each booking payment
4. **Refunds** — deposits refunded automatically when bookings are cancelled

### Key Decisions

- **Per-business deposit config** — each business chooses: no deposit, fixed amount, or percentage
- **Public bookings only** — manual dashboard bookings don't take payment (can be added later)
- **Connect onboarding via dashboard** — "Set up payments" button, not part of initial onboarding
- **Direct Stripe SDK** — no extra package; the `stripe/stripe-php` SDK bundled with Cashier is sufficient
- **No PostgreSQL migration needed** — all new data is either on Stripe or in existing/new columns

### Existing Infrastructure

- Bookings table already has: `deposit_amount`, `deposit_paid`, `payment_status`, `payment_intent_id`
- `TransactionLogger` already has `logBookingPayment()` and `logPlatformFee()` methods
- Cashier configured with `Business` as billable model, GBP currency
- Webhook route at `POST /stripe/webhook` with CSRF excluded

### Implementation Steps

#### Step 1: Migration — Add `stripe_connect_id` to businesses

New migration adding:

```
stripe_connect_id          — nullable, unique string
stripe_connect_onboarding_complete — boolean, default false
```

Business model helpers:

- `hasCompletedConnectOnboarding()` — checks both `stripe_connect_id` and `stripe_connect_onboarding_complete`
- `canAcceptPayments()` — completed onboarding + active subscription

#### Step 2: Business deposit settings

Added to `Business.settings` JSON (no migration):

```
deposits_enabled     — master toggle (default false)
deposit_type         — 'fixed' or 'percentage'
deposit_fixed_amount — in pence (e.g. 1000 = £10.00)
deposit_percentage   — e.g. 20 for 20%
```

New service: `app/Services/DepositCalculator.php` — calculates deposit in pence from business settings + booking total.

#### Step 3: Stripe Connect onboarding (backend)

New controller: `app/Http/Controllers/Dashboard/StripeConnectController.php`

| Method | Route | Purpose |
|--------|-------|---------|
| `index` | `GET /{handle}/payments` | Inertia page with connect status |
| `createAccount` | `POST /{handle}/payments/connect` | Creates Express account, redirects to Stripe onboarding |
| `refresh` | `GET /{handle}/payments/refresh` | Return URL for dropped onboarding |
| `updateSettings` | `POST /{handle}/payments/settings` | Save deposit config to business.settings |

Stripe API calls: `\Stripe\Account::create()`, `\Stripe\AccountLink::create()` (direct SDK, not Cashier).

#### Step 4: Stripe Connect webhook handling

New invokable controller: `app/Http/Controllers/ConnectWebhookController.php`

- Separate endpoint: `POST /stripe/connect-webhook` (excluded from CSRF)
- Separate webhook secret: `STRIPE_CONNECT_WEBHOOK_SECRET`
- Handles `account.updated` → sets `stripe_connect_onboarding_complete = true` when `charges_enabled` + `payouts_enabled`

#### Step 5: Payment at booking time (backend)

Modified: `BookingController::store()`

If business has deposits enabled + completed Connect onboarding:
1. Calculate deposit via `DepositCalculator`
2. Create Stripe Checkout Session with destination charge + 2.5% application fee
3. Update booking: `deposit_amount`, `payment_status = 'awaiting_deposit'`
4. Return checkout URL instead of confirmation redirect

If deposits NOT enabled: current flow unchanged.

#### Step 6: Payment webhook handling

Extended Connect webhook handles `checkout.session.completed`:
1. Look up booking by `metadata.booking_id`
2. Update: `deposit_paid = true`, `payment_status = 'deposit_paid'`, `payment_intent_id`
3. Log via `TransactionLogger::logBookingPayment()` + `logPlatformFee()`
4. Confirmation emails sent after payment succeeds (not at booking creation)

#### Step 7: Refund on cancellation

Modified: `Booking::cancel()` (or new `BookingCancellationService`)

If booking has paid deposit + payment_intent_id → `\Stripe\Refund::create()` with `reverse_transfer` + `refund_application_fee` to unwind the full chain.

#### Step 8: Booking form frontend changes

Modified: `resources/views/booking/show.blade.php`

- `submitBooking()` handles new response: redirect to Stripe Checkout if `requires_payment`, else current flow
- Deposit info shown in basket: "Deposit: £X.XX" + "Remaining balance of £Y.YY due on the day"

#### Step 9: Connect onboarding page (frontend)

New Inertia page: `resources/js/pages/dashboard/payments/index.tsx`

Three states: not started → pending → complete (with deposit settings form). Sidebar link added.

#### Step 10: Confirmation page updates

Modified: `resources/views/booking/confirmation.blade.php`

- Deposit paid: green checkmark + amount
- Remaining balance shown
- Deposit pending: warning if user abandoned Stripe checkout

### Critical Files

| File | Change |
|------|--------|
| `database/migrations/xxxx_add_stripe_connect_to_businesses.php` | New |
| `app/Models/Business.php` | Connect helpers, deposit settings accessors |
| `app/Services/DepositCalculator.php` | New |
| `app/Http/Controllers/Dashboard/StripeConnectController.php` | New |
| `app/Http/Controllers/ConnectWebhookController.php` | New |
| `app/Http/Controllers/BookingController.php` | Modify `store()` |
| `app/Models/Booking.php` | Modify `cancel()` |
| `resources/views/booking/show.blade.php` | Checkout redirect + deposit info |
| `resources/views/booking/confirmation.blade.php` | Deposit status |
| `resources/js/pages/dashboard/payments/index.tsx` | New |
| `routes/web.php` | Connect routes + webhook route |
| `bootstrap/app.php` | CSRF exclusion for connect webhook |

### Environment Variables

```
STRIPE_CONNECT_WEBHOOK_SECRET=whsec_xxx
```

Existing `STRIPE_KEY` and `STRIPE_SECRET` reused for Connect API calls.

### Key Routes

| Route | Action |
|-------|--------|
| `GET /{handle}/payments` | Connect status + deposit settings page |
| `POST /{handle}/payments/connect` | Create Express account + redirect to Stripe |
| `GET /{handle}/payments/refresh` | Resume dropped Stripe onboarding |
| `POST /{handle}/payments/settings` | Save deposit settings |
| `POST /stripe/connect-webhook` | Handle Connect + Checkout webhooks |

### Stripe Dashboard Setup (Post-Implementation)

1. Enable Connect in Stripe Dashboard (Express accounts)
2. Set Connect webhook URL: `{APP_URL}/stripe/connect-webhook`
3. Subscribe to events: `account.updated`, `checkout.session.completed`
4. Local dev: `stripe listen --forward-to heybertie.test/stripe/connect-webhook`

---

## Phase 8d: Embedded Stripe Connect Onboarding (Future)

### Context

Currently, businesses are redirected to Stripe's hosted onboarding page to set up payments and to Stripe's Express dashboard to manage their payment details. This works but takes users away from HeyBertie. Stripe offers embedded UI components that render inside our own pages, keeping users on-site for the entire flow.

### What This Replaces

- The external redirect to Stripe onboarding (Set Up Payments / Continue Setup)
- The external redirect to Stripe Express dashboard (Manage Payment Details)

### Approach

Use **Stripe Connect Embedded Components** (Account Onboarding Element + Account Management Element):

1. **Onboarding** — embed the Connect Onboarding component on the payments page. The business fills in their details without leaving HeyBertie. Stripe handles KYC/compliance behind the scenes.
2. **Account management** — embed the Connect Account Management component so businesses can update bank details, view payout history, manage tax info, and more — all within the dashboard.

### Technical Requirements

- Stripe Connect.js library loaded on the frontend
- Server-side: create an Account Session via `\Stripe\AccountSession::create()` to generate a client secret
- Frontend: render `<stripe-connect-onboarding>` and `<stripe-connect-account-management>` web components
- Remove the redirect-based flow and `RedirectModal` component
- Remove the `dashboard()` method and its route

### Benefits

- Users never leave HeyBertie — smoother, more professional experience
- Both onboarding and account management handled by the same toolkit
- Stripe still handles all compliance/KYC — no extra regulatory burden on us
