<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminStatsService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminDashboardController extends Controller
{
    public function __construct(private AdminStatsService $statsService) {}

    public function __invoke(Request $request): Response
    {
        return Inertia::render('admin/index', [
            'stats' => $this->statsService->getOverviewStats(),
        ]);
    }
}
