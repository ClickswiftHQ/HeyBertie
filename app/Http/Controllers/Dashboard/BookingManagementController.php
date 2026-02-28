<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\CancelBookingRequest;
use App\Http\Requests\Dashboard\StoreManualBookingRequest;
use App\Http\Requests\Dashboard\UpdateBookingNotesRequest;
use App\Mail\BookingConfirmation;
use App\Mail\NewBookingNotification;
use App\Models\Booking;
use App\Models\Business;
use App\Services\BookingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class BookingManagementController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var Business $business */
        $business = $request->attributes->get('currentBusiness');

        $status = $request->query('status');
        $from = $request->query('from', now()->toDateString());
        $to = $request->query('to', now()->addDays(7)->toDateString());

        $bookingsQuery = $business->bookings()
            ->with(['customer:id,name,email,phone', 'service:id,name', 'staffMember:id,display_name'])
            ->whereBetween('appointment_datetime', [$from.' 00:00:00', $to.' 23:59:59'])
            ->orderBy('appointment_datetime');

        if ($status && $status !== 'all') {
            $bookingsQuery->where('status', $status);
        }

        $bookings = $bookingsQuery->get();

        $grouped = $bookings->groupBy(fn (Booking $booking) => $booking->appointment_datetime->toDateString())
            ->map(fn ($dayBookings, $date) => [
                'date' => $date,
                'formatted_date' => \Carbon\Carbon::parse($date)->format('l, j F Y'),
                'bookings' => $dayBookings->map(fn (Booking $booking) => $this->formatBooking($booking))->values(),
            ])
            ->values();

        $statusCounts = [
            'all' => $business->bookings()
                ->whereBetween('appointment_datetime', [$from.' 00:00:00', $to.' 23:59:59'])
                ->count(),
            'pending' => $business->bookings()
                ->whereBetween('appointment_datetime', [$from.' 00:00:00', $to.' 23:59:59'])
                ->where('status', 'pending')
                ->count(),
            'confirmed' => $business->bookings()
                ->whereBetween('appointment_datetime', [$from.' 00:00:00', $to.' 23:59:59'])
                ->where('status', 'confirmed')
                ->count(),
            'completed' => $business->bookings()
                ->whereBetween('appointment_datetime', [$from.' 00:00:00', $to.' 23:59:59'])
                ->where('status', 'completed')
                ->count(),
        ];

        $locations = $business->locations()
            ->acceptingBookings()
            ->get(['id', 'name', 'slug']);

        $services = $business->services()
            ->active()
            ->orderBy('display_order')
            ->get(['id', 'name', 'duration_minutes', 'price', 'price_type', 'location_id'])
            ->map(fn ($service) => [
                'id' => $service->id,
                'name' => $service->name,
                'duration_minutes' => $service->duration_minutes,
                'price' => $service->price,
                'formatted_price' => $service->getFormattedPrice(),
                'location_id' => $service->location_id,
            ]);

        $recentCustomers = $business->customers()
            ->active()
            ->orderByDesc('last_visit')
            ->limit(20)
            ->get(['id', 'name', 'email', 'phone']);

        return Inertia::render('dashboard/calendar/index', [
            'bookingGroups' => $grouped,
            'statusCounts' => $statusCounts,
            'filters' => [
                'status' => $status ?? 'all',
                'from' => $from,
                'to' => $to,
            ],
            'locations' => $locations,
            'services' => $services,
            'recentCustomers' => $recentCustomers,
        ]);
    }

    public function storeManual(StoreManualBookingRequest $request, string $handle, BookingService $bookingService): RedirectResponse
    {
        /** @var Business $business */
        $business = $request->attributes->get('currentBusiness');

        $validated = $request->validated();

        // Verify location belongs to this business
        $location = $business->locations()->findOrFail($validated['location_id']);

        // Verify services belong to this business
        $business->services()
            ->active()
            ->whereIn('id', $validated['service_ids'])
            ->forLocation($location)
            ->get()
            ->whenEmpty(fn () => abort(422, 'No valid services selected.'));

        // Resolve customer details
        if (! empty($validated['customer_id'])) {
            $customer = $business->customers()->findOrFail($validated['customer_id']);
            $name = $customer->name;
            $email = $customer->email;
            $phone = $customer->phone ?? '';
        } else {
            $name = $validated['name'];
            $email = $validated['email'];
            $phone = $validated['phone'];
        }

        $booking = $bookingService->createMultiServiceBooking([
            'location_id' => $location->id,
            'service_ids' => $validated['service_ids'],
            'staff_member_id' => null,
            'appointment_datetime' => $validated['appointment_datetime'],
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'pet_name' => $validated['pet_name'],
            'pet_breed' => $validated['pet_breed'] ?? null,
            'pet_size' => $validated['pet_size'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        $booking->load(['location', 'business.owner', 'customer', 'staffMember']);

        try {
            if ($booking->customer->email) {
                Mail::to($booking->customer->email)->send(new BookingConfirmation($booking));
            }

            $businessEmail = $booking->location->email
                ?? $booking->business->email
                ?? $booking->business->owner->email;
            Mail::to($businessEmail)->send(new NewBookingNotification($booking));
        } catch (\Throwable $e) {
            report($e);
        }

        return redirect()
            ->route('business.calendar.index', $handle)
            ->with('success', 'Booking created successfully.');
    }

    public function show(Request $request, string $handle, int $booking): \Illuminate\Http\JsonResponse
    {
        /** @var Business $business */
        $business = $request->attributes->get('currentBusiness');

        $bookingModel = $business->bookings()
            ->with(['customer:id,name,email,phone', 'service:id,name', 'staffMember:id,display_name'])
            ->findOrFail($booking);

        return response()->json($this->formatBooking($bookingModel));
    }

    public function confirm(Request $request, string $handle, int $booking): RedirectResponse
    {
        /** @var Business $business */
        $business = $request->attributes->get('currentBusiness');

        $bookingModel = $business->bookings()->findOrFail($booking);
        $bookingModel->confirm();

        return back()->with('success', 'Booking confirmed.');
    }

    public function cancel(CancelBookingRequest $request, string $handle, int $booking): RedirectResponse
    {
        /** @var Business $business */
        $business = $request->attributes->get('currentBusiness');

        $bookingModel = $business->bookings()->findOrFail($booking);
        $bookingModel->cancel($request->user(), $request->validated('cancellation_reason'));

        return back()->with('success', 'Booking cancelled.');
    }

    public function complete(Request $request, string $handle, int $booking): RedirectResponse
    {
        /** @var Business $business */
        $business = $request->attributes->get('currentBusiness');

        $bookingModel = $business->bookings()->findOrFail($booking);
        $bookingModel->markAsCompleted();

        return back()->with('success', 'Booking marked as completed.');
    }

    public function noShow(Request $request, string $handle, int $booking): RedirectResponse
    {
        /** @var Business $business */
        $business = $request->attributes->get('currentBusiness');

        $bookingModel = $business->bookings()->findOrFail($booking);
        $bookingModel->markAsNoShow();

        return back()->with('success', 'Booking marked as no-show.');
    }

    public function updateNotes(UpdateBookingNotesRequest $request, string $handle, int $booking): RedirectResponse
    {
        /** @var Business $business */
        $business = $request->attributes->get('currentBusiness');

        $bookingModel = $business->bookings()->findOrFail($booking);
        $bookingModel->update(['pro_notes' => $request->validated('pro_notes')]);

        return back()->with('success', 'Notes updated.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formatBooking(Booking $booking): array
    {
        return [
            'id' => $booking->id,
            'booking_reference' => $booking->booking_reference,
            'appointment_datetime' => $booking->appointment_datetime->toIso8601String(),
            'time' => $booking->appointment_datetime->format('H:i'),
            'duration_minutes' => $booking->duration_minutes,
            'status' => $booking->status,
            'price' => $booking->price,
            'pet_name' => $booking->pet_name,
            'pet_breed' => $booking->pet_breed,
            'pet_size' => $booking->pet_size,
            'customer_notes' => $booking->customer_notes,
            'pro_notes' => $booking->pro_notes,
            'can_be_cancelled' => $booking->canBeCancelled(),
            'customer' => $booking->customer ? [
                'name' => $booking->customer->name,
                'email' => $booking->customer->email,
                'phone' => $booking->customer->phone,
            ] : null,
            'service' => $booking->service ? [
                'name' => $booking->service->name,
            ] : null,
            'staff_member' => $booking->staffMember ? [
                'name' => $booking->staffMember->display_name,
            ] : null,
        ];
    }
}
