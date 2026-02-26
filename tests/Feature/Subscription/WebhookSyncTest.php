<?php

use App\Models\Business;
use App\Models\SubscriptionStatus;
use App\Models\SubscriptionTier;
use App\Models\Transaction;

beforeEach(function () {
    // Create tiers BEFORE the factory to ensure stripe_price_id is set
    $this->freeTier = SubscriptionTier::updateOrCreate(['slug' => 'free'], ['name' => 'Free', 'trial_days' => 0, 'sort_order' => 1]);
    $this->soloTier = SubscriptionTier::updateOrCreate(['slug' => 'solo'], ['name' => 'Solo', 'monthly_price_pence' => 1999, 'sms_quota' => 30, 'trial_days' => 14, 'stripe_price_id' => 'price_solo_monthly', 'sort_order' => 2]);
    $this->salonTier = SubscriptionTier::updateOrCreate(['slug' => 'salon'], ['name' => 'Salon', 'monthly_price_pence' => 4999, 'staff_limit' => 5, 'location_limit' => 3, 'sms_quota' => 100, 'trial_days' => 14, 'stripe_price_id' => 'price_salon_monthly', 'sort_order' => 3]);
    SubscriptionStatus::firstOrCreate(['slug' => 'trial'], ['name' => 'Trial', 'sort_order' => 1]);
    SubscriptionStatus::firstOrCreate(['slug' => 'active'], ['name' => 'Active', 'sort_order' => 2]);
    SubscriptionStatus::firstOrCreate(['slug' => 'past_due'], ['name' => 'Past Due', 'sort_order' => 3]);
    SubscriptionStatus::firstOrCreate(['slug' => 'cancelled'], ['name' => 'Cancelled', 'sort_order' => 4]);

    $this->business = Business::factory()->solo()->completed()->verified()->create([
        'stripe_id' => 'cus_test_webhook',
    ]);
});

it('syncs tier and status on subscription created', function () {
    $payload = subscriptionPayload('customer.subscription.created', 'cus_test_webhook', 'active', 'price_solo_monthly');

    $this->postJson('/stripe/webhook', $payload)
        ->assertSuccessful();

    $this->business->refresh();

    expect($this->business->subscriptionTier->slug)->toBe('solo')
        ->and($this->business->subscriptionStatus->slug)->toBe('active');
});

it('syncs tier on subscription updated with new price', function () {
    // First create the subscription so Cashier can find it
    $this->business->subscriptions()->create([
        'type' => 'default',
        'stripe_id' => 'sub_test_update',
        'stripe_status' => 'active',
        'stripe_price' => 'price_solo_monthly',
    ]);

    $payload = subscriptionPayload('customer.subscription.updated', 'cus_test_webhook', 'active', 'price_salon_monthly', 'sub_test_update');

    $this->postJson('/stripe/webhook', $payload)
        ->assertSuccessful();

    $this->business->refresh();

    expect($this->business->subscriptionTier->slug)->toBe('salon');
});

it('downgrades to free on subscription deleted', function () {
    $this->business->subscriptions()->create([
        'type' => 'default',
        'stripe_id' => 'sub_test_delete',
        'stripe_status' => 'active',
        'stripe_price' => 'price_solo_monthly',
    ]);

    $payload = subscriptionPayload('customer.subscription.deleted', 'cus_test_webhook', 'canceled', 'price_solo_monthly', 'sub_test_delete');

    $this->postJson('/stripe/webhook', $payload)
        ->assertSuccessful();

    $this->business->refresh();

    expect($this->business->subscriptionTier->slug)->toBe('free')
        ->and($this->business->subscriptionStatus->slug)->toBe('cancelled');
});

it('logs transaction on invoice payment succeeded', function () {
    $payload = invoicePayload('cus_test_webhook', 'in_test_123', 1999, 'subscription_cycle');

    $this->postJson('/stripe/webhook', $payload)
        ->assertSuccessful();

    $transactions = Transaction::where('business_id', $this->business->id)
        ->where('type', 'subscription')
        ->get();

    expect($transactions)->toHaveCount(1);

    expect((float) $transactions->first()->amount)->toBe(19.99)
        ->and($transactions->first()->stripe_invoice_id)->toBe('in_test_123');
});

it('ignores invoice events that are not subscription-related', function () {
    $payload = invoicePayload('cus_test_webhook', 'in_test_456', 500, 'manual');

    $this->postJson('/stripe/webhook', $payload)
        ->assertSuccessful();

    expect(Transaction::where('business_id', $this->business->id)->count())->toBe(0);
});

it('maps past_due stripe status correctly', function () {
    $this->business->subscriptions()->create([
        'type' => 'default',
        'stripe_id' => 'sub_test_pastdue',
        'stripe_status' => 'active',
        'stripe_price' => 'price_solo_monthly',
    ]);

    $payload = subscriptionPayload('customer.subscription.updated', 'cus_test_webhook', 'past_due', 'price_solo_monthly', 'sub_test_pastdue');

    $this->postJson('/stripe/webhook', $payload)
        ->assertSuccessful();

    $this->business->refresh();

    expect($this->business->subscriptionStatus->slug)->toBe('past_due');
});

/**
 * Build a complete Stripe subscription webhook payload.
 *
 * @return array<string, mixed>
 */
function subscriptionPayload(string $type, string $customerId, string $status, string $priceId, ?string $subId = null): array
{
    $subId ??= 'sub_test_'.uniqid();

    return [
        'id' => 'evt_test_'.uniqid(),
        'type' => $type,
        'data' => [
            'object' => [
                'id' => $subId,
                'customer' => $customerId,
                'status' => $status,
                'metadata' => [],
                'current_period_start' => now()->timestamp,
                'current_period_end' => now()->addMonth()->timestamp,
                'items' => [
                    'data' => [
                        [
                            'id' => 'si_test_'.uniqid(),
                            'quantity' => 1,
                            'price' => [
                                'id' => $priceId,
                                'product' => 'prod_test_'.uniqid(),
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ];
}

/**
 * Build a Stripe invoice webhook payload.
 *
 * @return array<string, mixed>
 */
function invoicePayload(string $customerId, string $invoiceId, int $amountPaid, string $billingReason): array
{
    return [
        'id' => 'evt_test_'.uniqid(),
        'type' => 'invoice.payment_succeeded',
        'data' => [
            'object' => [
                'id' => $invoiceId,
                'customer' => $customerId,
                'amount_paid' => $amountPaid,
                'billing_reason' => $billingReason,
            ],
        ],
    ];
}
