<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Business;
use App\Models\User;
use Carbon\Carbon;

class AdminStatsService
{
    /**
     * @return array{totalBusinesses: int, verifiedBusinesses: int, pendingVerifications: int, totalUsers: int, registeredUsers: int, totalBookings: int, todaysBookings: int, weeklyBookings: int, monthlyBookings: int, mrr: float, subscriptionBreakdown: array<string, array<string, int>>, expiringTrials: list<array{id: int, name: string, handle: string, trial_ends_at: string, owner_name: string}>, recentSignups: list<array{id: int, name: string, handle: string, owner_name: string, tier: string, verification_status: string, created_at: string}>}
     */
    public function getOverviewStats(): array
    {
        $today = Carbon::today();
        $weekStart = Carbon::now()->startOfWeek();
        $weekEnd = Carbon::now()->endOfWeek();
        $monthStart = Carbon::now()->startOfMonth();
        $monthEnd = Carbon::now()->endOfMonth();

        return [
            'totalBusinesses' => Business::count(),
            'verifiedBusinesses' => Business::verified()->count(),
            'pendingVerifications' => Business::where('verification_status', 'pending')
                ->where('onboarding_completed', true)
                ->count(),
            'totalUsers' => User::count(),
            'registeredUsers' => User::where('is_registered', true)->count(),
            'totalBookings' => Booking::count(),
            'todaysBookings' => Booking::whereDate('appointment_datetime', $today)
                ->whereNotIn('status', ['cancelled'])
                ->count(),
            'weeklyBookings' => Booking::whereBetween('appointment_datetime', [$weekStart, $weekEnd])
                ->whereNotIn('status', ['cancelled'])
                ->count(),
            'monthlyBookings' => Booking::whereBetween('appointment_datetime', [$monthStart, $monthEnd])
                ->whereNotIn('status', ['cancelled'])
                ->count(),
            'mrr' => $this->calculateMrr(),
            'subscriptionBreakdown' => $this->getSubscriptionBreakdown(),
            'expiringTrials' => $this->getExpiringTrials(3),
            'recentSignups' => $this->getRecentSignups(7),
        ];
    }

    private function calculateMrr(): float
    {
        $activeRevenue = Business::query()
            ->whereHas('subscriptionStatus', fn ($q) => $q->whereIn('slug', ['active', 'trial']))
            ->join('subscription_tiers', 'businesses.subscription_tier_id', '=', 'subscription_tiers.id')
            ->where('subscription_tiers.monthly_price_pence', '>', 0)
            ->sum('subscription_tiers.monthly_price_pence');

        return round($activeRevenue / 100, 2);
    }

    /**
     * @return array<string, array<string, int>>
     */
    private function getSubscriptionBreakdown(): array
    {
        $results = Business::query()
            ->join('subscription_tiers', 'businesses.subscription_tier_id', '=', 'subscription_tiers.id')
            ->join('subscription_statuses', 'businesses.subscription_status_id', '=', 'subscription_statuses.id')
            ->selectRaw('subscription_tiers.slug as tier, subscription_statuses.slug as status, count(*) as count')
            ->groupBy('subscription_tiers.slug', 'subscription_statuses.slug')
            ->get();

        $breakdown = [];
        foreach ($results as $row) {
            $breakdown[$row->tier][$row->status] = (int) $row->count;
        }

        return $breakdown;
    }

    /**
     * @return list<array{id: int, name: string, handle: string, trial_ends_at: string, owner_name: string}>
     */
    private function getExpiringTrials(int $days): array
    {
        return Business::query()
            ->where('trial_ends_at', '>', now())
            ->where('trial_ends_at', '<=', now()->addDays($days))
            ->where('is_active', true)
            ->with('owner:id,name')
            ->orderBy('trial_ends_at')
            ->get()
            ->map(fn (Business $b) => [
                'id' => $b->id,
                'name' => $b->name,
                'handle' => $b->handle,
                'trial_ends_at' => $b->trial_ends_at->toIso8601String(),
                'owner_name' => $b->owner->name,
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{id: int, name: string, handle: string, owner_name: string, tier: string, verification_status: string, created_at: string}>
     */
    private function getRecentSignups(int $days): array
    {
        return Business::query()
            ->where('businesses.created_at', '>=', now()->subDays($days))
            ->with(['owner:id,name', 'subscriptionTier:id,slug'])
            ->orderByDesc('businesses.created_at')
            ->get()
            ->map(fn (Business $b) => [
                'id' => $b->id,
                'name' => $b->name,
                'handle' => $b->handle,
                'owner_name' => $b->owner->name,
                'tier' => $b->subscriptionTier->slug,
                'verification_status' => $b->verification_status,
                'created_at' => $b->created_at->toIso8601String(),
            ])
            ->values()
            ->all();
    }
}
