<?php

namespace App\Services;

use App\Models\Business;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

class AnalyticsService
{
    /**
     * @return array{totalRevenue: float, totalBookings: int, newCustomers: int, noShowRate: float}
     */
    public function getPeriodStats(Business $business, string $period): array
    {
        $start = $this->getPeriodStart($period);

        $revenueQuery = $business->transactions()->completed();
        $bookingsQuery = $business->bookings()->whereNotIn('status', ['cancelled']);
        $customersQuery = $business->customers();

        if ($start) {
            $revenueQuery->where('created_at', '>=', $start);
            $bookingsQuery->where('appointment_datetime', '>=', $start);
            $customersQuery->where('created_at', '>=', $start);
        }

        return [
            'totalRevenue' => (float) $revenueQuery->sum('amount'),
            'totalBookings' => $bookingsQuery->count(),
            'newCustomers' => $customersQuery->count(),
            'noShowRate' => $this->calculateNoShowRate($business, $start),
        ];
    }

    /**
     * @return array<int, array{label: string, value: float}>
     */
    public function getRevenueChartData(Business $business, string $period): array
    {
        $start = $this->getPeriodStart($period) ?? Carbon::parse($business->created_at);
        $granularity = $this->getGranularity($period);

        $query = $business->transactions()->completed()->where('created_at', '>=', $start);

        if ($granularity === 'daily') {
            $rows = $query
                ->selectRaw('date(created_at) as date_key, sum(amount) as total')
                ->groupBy('date_key')
                ->pluck('total', 'date_key');

            return $this->fillDailyBuckets($start, now(), $rows);
        }

        $rows = $query
            ->selectRaw("strftime('%Y-%W', created_at) as week_key, sum(amount) as total")
            ->groupBy('week_key')
            ->pluck('total', 'week_key');

        return $this->fillWeeklyBuckets($start, now(), $rows);
    }

    /**
     * @return array<int, array{label: string, value: int}>
     */
    public function getBookingsChartData(Business $business, string $period): array
    {
        $start = $this->getPeriodStart($period) ?? Carbon::parse($business->created_at);
        $granularity = $this->getGranularity($period);

        $query = $business->bookings()
            ->whereNotIn('status', ['cancelled'])
            ->where('appointment_datetime', '>=', $start);

        if ($granularity === 'daily') {
            $rows = $query
                ->selectRaw('date(appointment_datetime) as date_key, count(*) as total')
                ->groupBy('date_key')
                ->pluck('total', 'date_key');

            return $this->fillDailyBuckets($start, now(), $rows);
        }

        $rows = $query
            ->selectRaw("strftime('%Y-%W', appointment_datetime) as week_key, count(*) as total")
            ->groupBy('week_key')
            ->pluck('total', 'week_key');

        return $this->fillWeeklyBuckets($start, now(), $rows);
    }

    /**
     * @return array<int, array{name: string, bookings_count: int, revenue: float}>
     */
    public function getTopServices(Business $business, string $period, int $limit = 5): array
    {
        $start = $this->getPeriodStart($period);

        $query = $business->bookings()
            ->whereNotIn('status', ['cancelled'])
            ->whereNotNull('service_id')
            ->join('services', 'bookings.service_id', '=', 'services.id')
            ->selectRaw('services.name, count(*) as bookings_count, sum(bookings.price) as revenue')
            ->groupBy('services.id', 'services.name')
            ->orderByDesc('bookings_count')
            ->limit($limit);

        if ($start) {
            $query->where('appointment_datetime', '>=', $start);
        }

        return $query->get()->map(fn ($row) => [
            'name' => $row->name,
            'bookings_count' => (int) $row->bookings_count,
            'revenue' => (float) $row->revenue,
        ])->all();
    }

    /**
     * @return array<int, array{day: string, bookings_count: int}>
     */
    public function getBusiestDays(Business $business, string $period): array
    {
        $start = $this->getPeriodStart($period);

        $dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

        $query = $business->bookings()
            ->whereNotIn('status', ['cancelled'])
            ->selectRaw("cast(strftime('%w', appointment_datetime) as integer) as day_num, count(*) as bookings_count")
            ->groupBy('day_num')
            ->orderByDesc('bookings_count');

        if ($start) {
            $query->where('appointment_datetime', '>=', $start);
        }

        return $query->get()->map(fn ($row) => [
            'day' => $dayNames[(int) $row->day_num] ?? 'Unknown',
            'bookings_count' => (int) $row->bookings_count,
        ])->all();
    }

    private function getPeriodStart(string $period): ?Carbon
    {
        return match ($period) {
            '7' => Carbon::now()->subDays(7),
            '30' => Carbon::now()->subDays(30),
            '90' => Carbon::now()->subDays(90),
            default => null,
        };
    }

    private function getGranularity(string $period): string
    {
        return in_array($period, ['7', '30']) ? 'daily' : 'weekly';
    }

    private function calculateNoShowRate(Business $business, ?Carbon $start): float
    {
        $query = $business->bookings()
            ->where('appointment_datetime', '<', now())
            ->whereIn('status', ['completed', 'no_show']);

        if ($start) {
            $query->where('appointment_datetime', '>=', $start);
        }

        $total = $query->count();

        if ($total === 0) {
            return 0.0;
        }

        $noShows = $business->bookings()
            ->where('appointment_datetime', '<', now())
            ->whereIn('status', ['no_show']);

        if ($start) {
            $noShows->where('appointment_datetime', '>=', $start);
        }

        return round(($noShows->count() / $total) * 100, 1);
    }

    /**
     * @param  Collection<string, mixed>  $data
     * @return array<int, array{label: string, value: float|int}>
     */
    private function fillDailyBuckets(CarbonInterface $start, CarbonInterface $end, Collection $data): array
    {
        $result = [];

        foreach (CarbonPeriod::create(Carbon::parse($start)->startOfDay(), '1 day', Carbon::parse($end)->startOfDay()) as $date) {
            $key = $date->format('Y-m-d');
            $result[] = [
                'label' => $date->format('M j'),
                'value' => (float) ($data[$key] ?? 0),
            ];
        }

        return $result;
    }

    /**
     * @param  Collection<string, mixed>  $data
     * @return array<int, array{label: string, value: float|int}>
     */
    private function fillWeeklyBuckets(CarbonInterface $start, CarbonInterface $end, Collection $data): array
    {
        $result = [];

        $current = Carbon::parse($start)->startOfWeek();
        while ($current->lte($end)) {
            $key = $current->format('Y-W');
            $result[] = [
                'label' => $current->format('M j'),
                'value' => (float) ($data[$key] ?? 0),
            ];
            $current->addWeek();
        }

        return $result;
    }
}
