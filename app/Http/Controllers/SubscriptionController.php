<?php

namespace App\Http\Controllers;

use App\Models\Business;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function checkout(Request $request): mixed
    {
        /** @var Business $business */
        $business = $request->attributes->get('currentBusiness');
        $tier = $business->subscriptionTier;

        if (! $tier->stripe_price_id) {
            return redirect()->route('business.dashboard', $business->handle)
                ->with('error', 'No payment plan is configured for this tier.');
        }

        $subscription = $business->newSubscription('default', $tier->stripe_price_id);

        if ($business->trial_ends_at && $business->trial_ends_at->isFuture()) {
            $subscription->trialUntil($business->trial_ends_at);
        }

        return $subscription->checkout([
            'success_url' => route('subscription.success', $business->handle),
            'cancel_url' => route('subscription.cancelled', $business->handle),
        ]);
    }

    public function success(Request $request): RedirectResponse
    {
        /** @var Business $business */
        $business = $request->attributes->get('currentBusiness');

        return redirect()->route('business.dashboard', $business->handle)
            ->with('success', 'Your subscription is now active!');
    }

    public function cancelled(Request $request): RedirectResponse
    {
        /** @var Business $business */
        $business = $request->attributes->get('currentBusiness');

        return redirect()->route('business.dashboard', $business->handle)
            ->with('info', 'Checkout was cancelled. You can subscribe at any time.');
    }

    public function billingPortal(Request $request): mixed
    {
        /** @var Business $business */
        $business = $request->attributes->get('currentBusiness');

        return $business->redirectToBillingPortal(
            route('business.dashboard', $business->handle)
        );
    }
}
