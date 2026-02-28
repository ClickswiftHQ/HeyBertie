<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Services\AnalyticsService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AnalyticsController extends Controller
{
    public function __construct(private AnalyticsService $analyticsService) {}

    public function __invoke(Request $request): Response
    {
        /** @var Business $business */
        $business = $request->attributes->get('currentBusiness');

        $period = $request->query('period', '30');
        if (! in_array($period, ['7', '30', '90', 'all'])) {
            $period = '30';
        }

        return Inertia::render('dashboard/analytics/index', [
            'period' => $period,
            'stats' => $this->analyticsService->getPeriodStats($business, $period),
            'revenueChart' => $this->analyticsService->getRevenueChartData($business, $period),
            'bookingsChart' => $this->analyticsService->getBookingsChartData($business, $period),
            'topServices' => $this->analyticsService->getTopServices($business, $period),
            'busiestDays' => $this->analyticsService->getBusiestDays($business, $period),
        ]);
    }
}
