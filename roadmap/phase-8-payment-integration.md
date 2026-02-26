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
