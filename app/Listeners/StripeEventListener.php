<?php

namespace App\Listeners;

use App\Models\Business;
use App\Models\SubscriptionStatus;
use App\Models\SubscriptionTier;
use App\Services\TransactionLogger;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Events\WebhookReceived;

class StripeEventListener
{
    public function __construct(
        private TransactionLogger $transactionLogger,
    ) {}

    public function handle(WebhookReceived $event): void
    {
        match ($event->payload['type'] ?? null) {
            'customer.subscription.created',
            'customer.subscription.updated' => $this->handleSubscriptionUpdate($event->payload),
            'customer.subscription.deleted' => $this->handleSubscriptionDeleted($event->payload),
            'invoice.payment_succeeded' => $this->handleInvoicePaymentSucceeded($event->payload),
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function handleSubscriptionUpdate(array $payload): void
    {
        $data = $payload['data']['object'] ?? [];
        $business = $this->findBusinessByStripeId($data['customer'] ?? '');

        if (! $business) {
            return;
        }

        // Map Stripe price to our tier
        $stripePriceId = $data['items']['data'][0]['price']['id'] ?? null;

        if ($stripePriceId) {
            $tier = SubscriptionTier::where('stripe_price_id', $stripePriceId)->first();

            if ($tier) {
                $business->subscription_tier_id = $tier->id;
            }
        }

        // Map Stripe status to our status
        $stripeStatus = $data['status'] ?? '';
        $ourStatusSlug = $this->mapStripeStatus($stripeStatus);
        $status = SubscriptionStatus::findBySlug($ourStatusSlug);

        if ($status) {
            $business->subscription_status_id = $status->id;
        }

        $business->save();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function handleSubscriptionDeleted(array $payload): void
    {
        $data = $payload['data']['object'] ?? [];
        $business = $this->findBusinessByStripeId($data['customer'] ?? '');

        if (! $business) {
            return;
        }

        $freeTier = SubscriptionTier::findBySlug('free');
        $cancelledStatus = SubscriptionStatus::findBySlug('cancelled');

        if ($freeTier) {
            $business->subscription_tier_id = $freeTier->id;
        }

        if ($cancelledStatus) {
            $business->subscription_status_id = $cancelledStatus->id;
        }

        $business->save();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function handleInvoicePaymentSucceeded(array $payload): void
    {
        $data = $payload['data']['object'] ?? [];

        // Only log subscription invoices
        if (($data['billing_reason'] ?? '') !== 'subscription_cycle'
            && ($data['billing_reason'] ?? '') !== 'subscription_create') {
            return;
        }

        $business = $this->findBusinessByStripeId($data['customer'] ?? '');

        if (! $business) {
            return;
        }

        $invoiceId = $data['id'] ?? '';
        $amountPaid = ($data['amount_paid'] ?? 0) / 100;

        $this->transactionLogger->logSubscription($business, $invoiceId, $amountPaid);
    }

    private function findBusinessByStripeId(string $stripeId): ?Business
    {
        if (empty($stripeId)) {
            return null;
        }

        $business = Business::where('stripe_id', $stripeId)->first();

        if (! $business) {
            Log::warning('Stripe webhook: business not found', ['stripe_id' => $stripeId]);
        }

        return $business;
    }

    /**
     * Map Stripe subscription status to our internal status slug.
     */
    private function mapStripeStatus(string $stripeStatus): string
    {
        return match ($stripeStatus) {
            'active' => 'active',
            'trialing' => 'trial',
            'past_due' => 'past_due',
            'canceled', 'unpaid', 'incomplete_expired' => 'cancelled',
            'paused' => 'suspended',
            default => 'active',
        };
    }
}
