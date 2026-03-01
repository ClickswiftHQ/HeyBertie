<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\UpdateDepositSettingsRequest;
use App\Models\Business;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Stripe\Account;
use Stripe\AccountLink;
use Stripe\Stripe;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Throwable;

class StripeConnectController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var Business $business */
        $business = $request->attributes->get('currentBusiness');

        $connectStatus = 'not_started';

        if ($business->stripe_connect_id && $business->stripe_connect_onboarding_complete) {
            $connectStatus = 'complete';
        } elseif ($business->stripe_connect_id) {
            $connectStatus = 'pending';
        }

        $settings = $business->settings ?? [];

        return Inertia::render('dashboard/payments/index', [
            'connectStatus' => $connectStatus,
            'depositsEnabled' => $settings['deposits_enabled'] ?? false,
            'depositType' => $settings['deposit_type'] ?? 'fixed',
            'depositFixedAmount' => ($settings['deposit_fixed_amount'] ?? 1000) / 100,
            'depositPercentage' => $settings['deposit_percentage'] ?? 20,
        ]);
    }

    public function createAccount(Request $request): SymfonyResponse
    {
        /** @var Business $business */
        $business = $request->attributes->get('currentBusiness');

        if ($business->stripe_connect_id) {
            return $this->redirectToOnboarding($business);
        }

        Stripe::setApiKey(config('cashier.secret'));

        $account = Account::create([
            'type' => 'express',
            'country' => 'GB',
            'email' => $business->email,
            'capabilities' => [
                'card_payments' => ['requested' => true],
                'transfers' => ['requested' => true],
            ],
            'business_profile' => [
                'name' => $business->name,
                'url' => route('business.show', $business->handle),
            ],
        ]);

        $business->update(['stripe_connect_id' => $account->id]);

        return $this->redirectToOnboarding($business);
    }

    public function refresh(Request $request): RedirectResponse
    {
        /** @var Business $business */
        $business = $request->attributes->get('currentBusiness');

        if (! $business->stripe_connect_id) {
            return redirect()->route('business.connect.index', $business->handle);
        }

        return $this->redirectToOnboarding($business);
    }

    public function dashboard(Request $request): SymfonyResponse
    {
        /** @var Business $business */
        $business = $request->attributes->get('currentBusiness');

        if (! $business->hasCompletedConnectOnboarding()) {
            return redirect()->route('business.connect.index', $business->handle);
        }

        try {
            Stripe::setApiKey(config('cashier.secret'));

            $loginLink = Account::createLoginLink($business->stripe_connect_id);

            return Inertia::location($loginLink->url);
        } catch (Throwable) {
            return redirect()->route('business.connect.index', $business->handle)
                ->with('error', 'Unable to access payment dashboard. Please try again.');
        }
    }

    public function updateSettings(UpdateDepositSettingsRequest $request): RedirectResponse
    {
        /** @var Business $business */
        $business = $request->attributes->get('currentBusiness');

        if (! $business->hasCompletedConnectOnboarding()) {
            return back()->with('error', 'Please complete Stripe Connect setup before configuring deposits.');
        }

        $settings = $business->settings ?? [];

        $settings['deposits_enabled'] = $request->boolean('deposits_enabled');
        $settings['deposit_type'] = $request->validated('deposit_type') ?? 'fixed';

        if ($settings['deposit_type'] === 'fixed') {
            // Convert pounds to pence for storage
            $settings['deposit_fixed_amount'] = (int) round($request->validated('deposit_fixed_amount') * 100);
        }

        if ($settings['deposit_type'] === 'percentage') {
            $settings['deposit_percentage'] = (int) $request->validated('deposit_percentage');
        }

        $business->update(['settings' => $settings]);

        return back()->with('success', 'Deposit settings updated.');
    }

    private function redirectToOnboarding(Business $business): SymfonyResponse
    {
        Stripe::setApiKey(config('cashier.secret'));

        $link = AccountLink::create([
            'account' => $business->stripe_connect_id,
            'refresh_url' => route('business.connect.refresh', $business->handle),
            'return_url' => route('business.connect.index', $business->handle),
            'type' => 'account_onboarding',
        ]);

        return Inertia::location($link->url);
    }
}
