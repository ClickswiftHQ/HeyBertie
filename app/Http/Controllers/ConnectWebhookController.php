<?php

namespace App\Http\Controllers;

use App\Mail\BookingConfirmation;
use App\Mail\NewBookingNotification;
use App\Models\Booking;
use App\Models\Business;
use App\Services\TransactionLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Stripe\Webhook;

class ConnectWebhookController extends Controller
{
    public function __invoke(Request $request, TransactionLogger $transactionLogger): JsonResponse
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');

        try {
            $event = Webhook::constructEvent(
                $payload,
                $signature,
                config('services.stripe.connect_webhook_secret'),
            );
        } catch (\Exception $e) {
            Log::warning('Connect webhook signature verification failed', ['error' => $e->getMessage()]);

            return response()->json(['error' => 'Invalid signature'], 400);
        }

        match ($event->type) {
            'account.updated' => $this->handleAccountUpdated($event),
            'checkout.session.completed' => $this->handleCheckoutSessionCompleted($event, $transactionLogger),
            default => null,
        };

        return response()->json(['status' => 'ok']);
    }

    private function handleAccountUpdated(\Stripe\Event $event): void
    {
        $account = $event->data->object;

        $business = Business::where('stripe_connect_id', $account->id)->first();

        if (! $business) {
            return;
        }

        if ($account->charges_enabled && $account->payouts_enabled) {
            $business->update(['stripe_connect_onboarding_complete' => true]);
        }
    }

    private function handleCheckoutSessionCompleted(\Stripe\Event $event, TransactionLogger $transactionLogger): void
    {
        $session = $event->data->object;
        $bookingId = $session->metadata->booking_id ?? null;

        if (! $bookingId) {
            return;
        }

        $booking = Booking::find($bookingId);

        if (! $booking) {
            Log::warning('Connect webhook: booking not found', ['booking_id' => $bookingId]);

            return;
        }

        if ($booking->deposit_paid) {
            return;
        }

        $booking->update([
            'deposit_paid' => true,
            'payment_status' => 'deposit_paid',
            'payment_intent_id' => $session->payment_intent,
        ]);

        // Log transactions
        $depositAmount = $booking->deposit_amount;
        $transactionLogger->logBookingPayment($booking, $session->payment_intent, (float) $depositAmount);

        $platformFee = round((float) $depositAmount * 0.025, 2);
        $transactionLogger->logPlatformFee($booking, $platformFee);

        // Send confirmation emails now that payment is confirmed
        $booking->load(['location', 'business.owner', 'customer', 'staffMember']);

        try {
            Mail::to($booking->customer->email)->send(new BookingConfirmation($booking));

            $businessEmail = $booking->location->email
                ?? $booking->business->email
                ?? $booking->business->owner->email;
            Mail::to($businessEmail)->send(new NewBookingNotification($booking));
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
