<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Services\DashboardStatsService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(private DashboardStatsService $statsService) {}

    public function __invoke(Request $request): Response
    {
        /** @var Business $business */
        $business = $request->attributes->get('currentBusiness');

        return Inertia::render('dashboard/index', [
            'stats' => $this->statsService->getOverviewStats($business),
            'upcomingBookings' => $this->statsService->getUpcomingBookings($business, 5),
            'recentActivity' => $this->statsService->getRecentActivity($business, 10),
        ]);
    }
}
