<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookingRequest;
use App\Mail\BookingConfirmation;
use App\Mail\NewBookingNotification;
use App\Models\Booking;
use App\Models\Breed;
use App\Models\Business;
use App\Models\Location;
use App\Models\StaffMember;
use App\Services\AvailabilityService;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function __construct(
        private AvailabilityService $availabilityService,
        private BookingService $bookingService,
    ) {}

    public function show(string $handle, string $locationSlug, Request $request): View
    {
        $business = $this->loadBookableBusiness($handle);
        $location = $business->locations->firstWhere('slug', $locationSlug);

        abort_if(! $location || ! $location->accepts_bookings, 404);

        $services = $business->services
            ->filter(fn ($s) => $s->location_id === null || $s->location_id === $location->id)
            ->values();

        $staffSelectionEnabled = $business->settings['staff_selection_enabled'] ?? false;

        $staff = $staffSelectionEnabled
            ? StaffMember::query()
                ->where('business_id', $business->id)
                ->active()
                ->acceptingBookings()
                ->worksAtLocation($location)
                ->get()
            : collect();

        $preselectedServiceIds = $request->input('services', []);

        $breeds = Breed::query()
            ->with('species:id,name')
            ->orderBy('species_id')
            ->orderBy('sort_order')
            ->get(['id', 'name', 'species_id'])
            ->map(fn (Breed $breed) => [
                'name' => $breed->name,
                'species' => $breed->species->name,
            ])
            ->values();

        return view('booking.show', [
            'business' => $business,
            'location' => $location,
            'services' => $services,
            'staff' => $staff,
            'staffSelectionEnabled' => $staffSelectionEnabled,
            'preselectedServiceIds' => array_map('intval', (array) $preselectedServiceIds),
            'breeds' => $breeds,
        ]);
    }

    public function store(string $handle, string $locationSlug, StoreBookingRequest $request): JsonResponse
    {
        $business = $this->loadBookableBusiness($handle);
        $location = $business->locations->firstWhere('slug', $locationSlug);

        abort_if(! $location || ! $location->accepts_bookings, 404);

        try {
            $booking = $this->bookingService->createMultiServiceBooking([
                'location_id' => $location->id,
                'service_ids' => $request->validated('service_ids'),
                'staff_member_id' => $request->validated('staff_member_id'),
                'appointment_datetime' => $request->validated('appointment_datetime'),
                'name' => $request->validated('name'),
                'email' => $request->validated('email'),
                'phone' => $request->validated('phone'),
                'pet_name' => $request->validated('pet_name'),
                'pet_breed' => $request->validated('pet_breed'),
                'pet_size' => $request->validated('pet_size'),
                'notes' => $request->validated('notes'),
            ]);

            $booking->load(['location', 'business.owner', 'customer', 'staffMember']);

            try {
                Mail::to($booking->customer->email)->send(new BookingConfirmation($booking));

                $businessEmail = $booking->location->email
                    ?? $booking->business->email
                    ?? $booking->business->owner->email;
                Mail::to($businessEmail)->send(new NewBookingNotification($booking));
            } catch (\Throwable $e) {
                report($e);
            }

            return response()->json([
                'success' => true,
                'booking_reference' => $booking->booking_reference,
                'redirect' => route('booking.confirmation', [
                    'handle' => $handle,
                    'locationSlug' => $locationSlug,
                    'ref' => $booking->booking_reference,
                ]),
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 409);
        }
    }

    public function confirmation(string $handle, string $locationSlug, Request $request): View
    {
        $business = $this->loadBookableBusiness($handle);
        $location = $business->locations->firstWhere('slug', $locationSlug);

        abort_if(! $location, 404);

        $booking = Booking::query()
            ->where('business_id', $business->id)
            ->where('location_id', $location->id)
            ->where('booking_reference', $request->query('ref'))
            ->with('items')
            ->firstOrFail();

        $manageUrl = URL::signedRoute('customer.bookings.show', [
            'ref' => $booking->booking_reference,
        ]);

        return view('booking.confirmation', [
            'business' => $business,
            'location' => $location,
            'booking' => $booking,
            'manageUrl' => $manageUrl,
        ]);
    }

    public function availableDates(Location $location, Request $request): JsonResponse
    {
        abort_if(! $location->is_active || ! $location->accepts_bookings, 404);

        $request->validate([
            'duration' => ['required', 'integer', 'min:15', 'max:480'],
            'staff' => ['nullable', 'integer', 'exists:staff_members,id'],
        ]);

        $staff = $request->input('staff') ? StaffMember::find($request->input('staff')) : null;

        $dates = $this->availabilityService->getAvailableDates(
            $location,
            $staff,
            (int) $request->input('duration'),
            $location->advance_booking_days,
        );

        return response()->json([
            'dates' => $dates,
            'advance_booking_days' => $location->advance_booking_days,
        ]);
    }

    public function timeSlots(Location $location, Request $request): JsonResponse
    {
        abort_if(! $location->is_active || ! $location->accepts_bookings, 404);

        $request->validate([
            'date' => ['required', 'date', 'after_or_equal:today'],
            'duration' => ['required', 'integer', 'min:15', 'max:480'],
            'staff' => ['nullable', 'integer', 'exists:staff_members,id'],
        ]);

        $staff = $request->input('staff') ? StaffMember::find($request->input('staff')) : null;
        $date = \Carbon\Carbon::parse($request->input('date'));

        $slots = $this->availabilityService->getAvailableSlots(
            $location,
            $date,
            $staff,
            (int) $request->input('duration'),
        );

        // Filter out slots that don't meet minimum notice requirement
        $minNotice = now()->addHours($location->min_notice_hours);
        $slots = array_values(array_filter($slots, function ($slot) use ($date, $minNotice) {
            $slotTime = $date->copy()->setTimeFromTimeString($slot['time']);

            return $slotTime->gte($minNotice);
        }));

        // Add period grouping
        $slots = array_map(function ($slot) {
            $hour = (int) explode(':', $slot['time'])[0];
            $slot['period'] = match (true) {
                $hour < 12 => 'morning',
                $hour < 17 => 'afternoon',
                default => 'evening',
            };

            return $slot;
        }, $slots);

        return response()->json([
            'date' => $date->toDateString(),
            'slots' => $slots,
        ]);
    }

    private function loadBookableBusiness(string $handle): Business
    {
        return Business::query()
            ->where('handle', $handle)
            ->where('is_active', true)
            ->where('onboarding_completed', true)
            ->whereHas('subscriptionTier', fn ($q) => $q->where('slug', '!=', 'free'))
            ->with([
                'locations' => fn ($q) => $q->where('is_active', true),
                'services' => fn ($q) => $q->where('is_active', true)->orderBy('display_order'),
                'subscriptionTier:id,slug',
            ])
            ->firstOrFail();
    }
}
