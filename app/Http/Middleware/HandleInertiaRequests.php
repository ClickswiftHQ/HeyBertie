<?php

namespace App\Http\Middleware;

use App\Models\Business;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'currentBusiness' => fn () => $this->getCurrentBusiness($request),
            'userBusinesses' => fn () => $this->getUserBusinesses($request),
        ];
    }

    /**
     * @return array{id: int, name: string, handle: string, logo_url: string|null, subscription_tier: string, has_active_subscription: bool, on_trial: bool, trial_days_remaining: int|null}|null
     */
    private function getCurrentBusiness(Request $request): ?array
    {
        $business = $request->attributes->get('currentBusiness');
        if (! $business) {
            return null;
        }

        return [
            'id' => $business->id,
            'name' => $business->name,
            'handle' => $business->handle,
            'logo_url' => $business->logo_url,
            'subscription_tier' => $business->subscriptionTier->slug ?? 'free',
            'has_active_subscription' => $business->hasActiveSubscription(),
            'on_trial' => $business->onGenericTrial(),
            'trial_days_remaining' => $business->trial_ends_at && $business->trial_ends_at->isFuture()
                ? (int) ceil(now()->floatDiffInDays($business->trial_ends_at, false))
                : null,
        ];
    }

    /**
     * @return list<array{id: int, name: string, handle: string, logo_url: string|null}>
     */
    private function getUserBusinesses(Request $request): array
    {
        if (! $request->user()) {
            return [];
        }

        return Business::query()
            ->where('onboarding_completed', true)
            ->where('is_active', true)
            ->where(fn ($q) => $q
                ->where('owner_user_id', $request->user()->id)
                ->orWhereHas('users', fn ($q) => $q->where('user_id', $request->user()->id))
            )
            ->select(['id', 'name', 'handle', 'logo_url'])
            ->get()
            ->toArray();
    }
}
