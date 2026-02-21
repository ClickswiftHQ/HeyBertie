<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Business;
use App\Models\Customer;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DashboardStatsService
{
    /**
     * @return array{todaysBookings: int, weeklyRevenue: float, totalCustomers: int, pageViews: int, pendingBookings: int, averageRating: float|null, noShowRate: float, monthlyBookings: int}
     */
    public function getOverviewStats(Business $business): array
    {
        $today = Carbon::today();
        $weekStart = Carbon::now()->startOfWeek();
        $weekEnd = Carbon::now()->endOfWeek();
        $monthStart = Carbon::now()->startOfMonth();
        $monthEnd = Carbon::now()->endOfMonth();
        $sevenDaysAgo = Carbon::now()->subDays(7);

        $todaysBookings = $business->bookings()
            ->whereDate('appointment_datetime', $today)
            ->whereNotIn('status', ['cancelled'])
            ->count();

        $weeklyRevenue = (float) $business->transactions()
            ->completed()
            ->forPeriod($weekStart, $weekEnd)
            ->sum('amount');

        $totalCustomers = $business->customers()->active()->count();

        $pageViews = $business->pageViews()
            ->forPeriod($sevenDaysAgo, Carbon::now())
            ->count();

        $pendingBookings = $business->bookings()
            ->status('pending')
            ->upcoming()
            ->count();

        $averageRating = $business->getAverageRating();
        if ($averageRating !== null) {
            $averageRating = round($averageRating, 1);
        }

        $monthlyBookings = $business->bookings()
            ->whereBetween('appointment_datetime', [$monthStart, $monthEnd])
            ->whereNotIn('status', ['cancelled'])
            ->count();

        $noShowRate = $this->calculateNoShowRate($business);

        return [
            'todaysBookings' => $todaysBookings,
            'weeklyRevenue' => $weeklyRevenue,
            'totalCustomers' => $totalCustomers,
            'pageViews' => $pageViews,
            'pendingBookings' => $pendingBookings,
            'averageRating' => $averageRating,
            'noShowRate' => $noShowRate,
            'monthlyBookings' => $monthlyBookings,
        ];
    }

    /**
     * @return Collection<int, Booking>
     */
    public function getUpcomingBookings(Business $business, int $limit = 5): Collection
    {
        return $business->bookings()
            ->upcoming()
            ->with(['service:id,name', 'customer:id,name'])
            ->orderBy('appointment_datetime')
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, array{id: string, type: string, description: string, datetime: string}>
     */
    public function getRecentActivity(Business $business, int $limit = 10): Collection
    {
        $recentBookings = $business->bookings()
            ->with('customer:id,name')
            ->latest('created_at')
            ->limit($limit)
            ->get()
            ->map(fn (Booking $booking) => [
                'id' => 'booking-'.$booking->id,
                'type' => $booking->status === 'cancelled' ? 'booking_cancelled' : 'booking_created',
                'description' => $this->describeBookingActivity($booking),
                'datetime' => $booking->created_at->toIso8601String(),
            ]);

        $recentCustomers = $business->customers()
            ->latest('created_at')
            ->limit($limit)
            ->get()
            ->map(fn (Customer $customer) => [
                'id' => 'customer-'.$customer->id,
                'type' => 'customer_created',
                'description' => $customer->name.' registered as a new customer',
                'datetime' => $customer->created_at->toIso8601String(),
            ]);

        $recentReviews = $business->reviews()
            ->published()
            ->with('user:id,name')
            ->latest('created_at')
            ->limit($limit)
            ->get()
            ->map(fn ($review) => [
                'id' => 'review-'.$review->id,
                'type' => 'review_received',
                'description' => ($review->user->name ?? 'Someone').' left a '.$review->rating.'-star review',
                'datetime' => $review->created_at->toIso8601String(),
            ]);

        return $recentBookings
            ->concat($recentCustomers)
            ->concat($recentReviews)
            ->sortByDesc('datetime')
            ->take($limit)
            ->values();
    }

    private function calculateNoShowRate(Business $business): float
    {
        $thirtyDaysAgo = Carbon::now()->subDays(30);

        $totalCompleted = $business->bookings()
            ->where('appointment_datetime', '<', now())
            ->where('appointment_datetime', '>=', $thirtyDaysAgo)
            ->whereIn('status', ['completed', 'no_show'])
            ->count();

        if ($totalCompleted === 0) {
            return 0.0;
        }

        $noShows = $business->bookings()
            ->where('appointment_datetime', '<', now())
            ->where('appointment_datetime', '>=', $thirtyDaysAgo)
            ->status('no_show')
            ->count();

        return round(($noShows / $totalCompleted) * 100, 1);
    }

    private function describeBookingActivity(Booking $booking): string
    {
        $customerName = $booking->customer->name ?? 'A customer';

        if ($booking->status === 'cancelled') {
            return $customerName.' cancelled their appointment';
        }

        return $customerName.' booked an appointment';
    }
}
