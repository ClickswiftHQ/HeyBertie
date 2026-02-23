<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Services\BookingService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

class CustomerBookingController extends Controller
{
    public function __construct(
        private BookingService $bookingService,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();

        $upcomingBookings = $user->bookings()
            ->upcoming()
            ->with(['business', 'location', 'items'])
            ->orderBy('appointment_datetime')
            ->get();

        $pastBookings = $user->bookings()
            ->where(function ($q) {
                $q->where('appointment_datetime', '<=', now())
                    ->orWhereIn('status', ['cancelled', 'no_show']);
            })
            ->with(['business', 'location', 'items'])
            ->orderByDesc('appointment_datetime')
            ->limit(20)
            ->get();

        return view('customer.bookings.index', [
            'upcomingBookings' => $upcomingBookings,
            'pastBookings' => $pastBookings,
        ]);
    }

    public function show(string $ref, Request $request): View
    {
        $booking = $this->findBookingByRef($ref);
        $this->authorizeBookingAccess($booking, $request);

        $booking->load(['business', 'location', 'items', 'staffMember']);

        $cancelUrl = $booking->canBeCancelled()
            ? URL::signedRoute('customer.bookings.cancel', ['ref' => $booking->booking_reference])
            : null;

        $rescheduleUrl = $booking->canBeRescheduled()
            ? URL::signedRoute('customer.bookings.reschedule', ['ref' => $booking->booking_reference])
            : null;

        return view('customer.bookings.show', [
            'booking' => $booking,
            'cancelUrl' => $cancelUrl,
            'rescheduleUrl' => $rescheduleUrl,
        ]);
    }

    public function cancel(string $ref, Request $request): RedirectResponse
    {
        $booking = $this->findBookingByRef($ref);
        $this->authorizeBookingAccess($booking, $request);

        if (! $booking->canBeCancelled()) {
            return redirect()->back()->with('error', 'This booking cannot be cancelled.');
        }

        $reason = $request->input('reason', 'Cancelled by customer');
        $user = $request->user() ?? $booking->customer->user;
        $booking->cancel($user, $reason);

        $showUrl = URL::signedRoute('customer.bookings.show', ['ref' => $booking->booking_reference]);

        return redirect($showUrl)->with('success', 'Your booking has been cancelled.');
    }

    public function reschedule(string $ref, Request $request): View
    {
        $booking = $this->findBookingByRef($ref);
        $this->authorizeBookingAccess($booking, $request);

        if (! $booking->canBeRescheduled()) {
            abort(403, 'This booking cannot be rescheduled.');
        }

        $booking->load(['business', 'location', 'items', 'staffMember']);

        $processUrl = URL::signedRoute('customer.bookings.process-reschedule', ['ref' => $booking->booking_reference]);

        return view('customer.bookings.reschedule', [
            'booking' => $booking,
            'processUrl' => $processUrl,
        ]);
    }

    public function processReschedule(string $ref, Request $request): RedirectResponse
    {
        $booking = $this->findBookingByRef($ref);
        $this->authorizeBookingAccess($booking, $request);

        $request->validate([
            'appointment_datetime' => ['required', 'date', 'after:now'],
        ]);

        try {
            $this->bookingService->rescheduleBooking(
                $booking,
                Carbon::parse($request->input('appointment_datetime')),
            );

            $showUrl = URL::signedRoute('customer.bookings.show', ['ref' => $booking->booking_reference]);

            return redirect($showUrl)->with('success', 'Your booking has been rescheduled.');
        } catch (\RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    private function findBookingByRef(string $ref): Booking
    {
        return Booking::query()
            ->where('booking_reference', $ref)
            ->with('customer')
            ->firstOrFail();
    }

    private function authorizeBookingAccess(Booking $booking, Request $request): void
    {
        if ($request->hasValidSignature()) {
            return;
        }

        $user = $request->user();

        if ($user && $booking->customer->user_id === $user->id) {
            return;
        }

        abort(403, 'You do not have access to this booking.');
    }
}
