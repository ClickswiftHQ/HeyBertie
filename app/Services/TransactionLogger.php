<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Business;
use App\Models\Transaction;

class TransactionLogger
{
    public function logSubscription(Business $business, string $invoiceId, float $amount): Transaction
    {
        return Transaction::create([
            'business_id' => $business->id,
            'type' => 'subscription',
            'amount' => $amount,
            'stripe_invoice_id' => $invoiceId,
            'status' => 'completed',
            'description' => "Subscription payment - {$business->subscriptionTier->name} tier",
        ]);
    }

    public function logBookingPayment(Booking $booking, string $paymentIntentId, float $amount): Transaction
    {
        return Transaction::create([
            'business_id' => $booking->business_id,
            'booking_id' => $booking->id,
            'type' => 'booking_payment',
            'amount' => $amount,
            'stripe_payment_intent_id' => $paymentIntentId,
            'status' => 'completed',
            'description' => "Payment for booking #{$booking->id}",
        ]);
    }

    public function logPlatformFee(Booking $booking, float $amount): Transaction
    {
        return Transaction::create([
            'business_id' => $booking->business_id,
            'booking_id' => $booking->id,
            'type' => 'platform_fee',
            'amount' => $amount,
            'status' => 'completed',
            'description' => "Platform fee (2.5%) for booking #{$booking->id}",
        ]);
    }
}
