<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\UpdateBusinessSettingsRequest;
use App\Models\Business;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BusinessSettingsController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var Business $business */
        $business = $request->attributes->get('currentBusiness');
        $settings = $business->settings ?? [];

        return Inertia::render('dashboard/settings/index', [
            'settings' => [
                'auto_confirm_bookings' => $settings['auto_confirm_bookings'] ?? true,
                'staff_selection_enabled' => $settings['staff_selection_enabled'] ?? false,
            ],
        ]);
    }

    public function update(UpdateBusinessSettingsRequest $request): RedirectResponse
    {
        /** @var Business $business */
        $business = $request->attributes->get('currentBusiness');

        $currentSettings = $business->settings ?? [];
        $business->update([
            'settings' => array_merge($currentSettings, $request->validated()),
        ]);

        return back()->with('success', 'Settings saved.');
    }
}
