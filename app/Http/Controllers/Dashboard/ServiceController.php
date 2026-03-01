<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\ReorderServicesRequest;
use App\Http\Requests\Dashboard\StoreServiceRequest;
use App\Http\Requests\Dashboard\UpdateServiceRequest;
use App\Models\Business;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ServiceController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var Business $business */
        $business = $request->attributes->get('currentBusiness');

        $services = $business->services()
            ->withCount('bookings')
            ->orderBy('display_order')
            ->orderBy('name')
            ->get()
            ->map(fn (Service $service) => [
                'id' => $service->id,
                'name' => $service->name,
                'description' => $service->description,
                'duration_minutes' => $service->duration_minutes,
                'price' => $service->price,
                'price_type' => $service->price_type,
                'formatted_price' => $service->getFormattedPrice(),
                'display_order' => $service->display_order,
                'is_active' => $service->is_active,
                'is_featured' => $service->is_featured,
                'bookings_count' => $service->bookings_count,
            ]);

        return Inertia::render('dashboard/services/index', [
            'services' => $services,
        ]);
    }

    public function store(StoreServiceRequest $request): RedirectResponse
    {
        /** @var Business $business */
        $business = $request->attributes->get('currentBusiness');

        $maxOrder = $business->services()->max('display_order') ?? -1;

        $business->services()->create([
            ...$request->validated(),
            'display_order' => $maxOrder + 1,
            'is_active' => true,
        ]);

        return back()->with('success', 'Service created.');
    }

    public function update(UpdateServiceRequest $request, string $handle, int $service): RedirectResponse
    {
        /** @var Business $business */
        $business = $request->attributes->get('currentBusiness');

        $serviceModel = $business->services()->findOrFail($service);
        $serviceModel->update($request->validated());

        return back()->with('success', 'Service updated.');
    }

    public function destroy(Request $request, string $handle, int $service): RedirectResponse
    {
        /** @var Business $business */
        $business = $request->attributes->get('currentBusiness');

        $serviceModel = $business->services()->findOrFail($service);
        $serviceModel->delete();

        return back()->with('success', 'Service deleted.');
    }

    public function reorder(ReorderServicesRequest $request): RedirectResponse
    {
        /** @var Business $business */
        $business = $request->attributes->get('currentBusiness');

        foreach ($request->validated('order') as $index => $serviceId) {
            $business->services()
                ->where('id', $serviceId)
                ->update(['display_order' => $index]);
        }

        return back()->with('success', 'Services reordered.');
    }

    public function toggleActive(Request $request, string $handle, int $service): RedirectResponse
    {
        /** @var Business $business */
        $business = $request->attributes->get('currentBusiness');

        $serviceModel = $business->services()->findOrFail($service);
        $serviceModel->update(['is_active' => ! $serviceModel->is_active]);

        return back()->with('success', $serviceModel->is_active ? 'Service activated.' : 'Service deactivated.');
    }
}
