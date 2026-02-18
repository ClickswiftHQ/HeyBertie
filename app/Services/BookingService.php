<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Location;
use App\Models\StaffMember;
use Carbon\Carbon;

class BookingService
{
    public function __construct(
        private AvailabilityService $availabilityService
    ) {}

    /**
     * @return array{available: bool, reason: string|null}
     */
    public function checkAvailability(Location $location, Carbon $start, int $duration, ?StaffMember $staff = null): array
    {
        if (! $location->is_active || ! $location->accepts_bookings) {
            return ['available' => false, 'reason' => 'This location is not accepting bookings.'];
        }

        if ($start->lt(now()->addHours($location->min_notice_hours))) {
            return ['available' => false, 'reason' => "Bookings require at least {$location->min_notice_hours} hours notice."];
        }

        if ($start->gt(now()->addDays($location->advance_booking_days))) {
            return ['available' => false, 'reason' => "Bookings can only be made up to {$location->advance_booking_days} days in advance."];
        }

        if (! $this->availabilityService->isTimeSlotAvailable($location, $start, $duration, $staff)) {
            return ['available' => false, 'reason' => 'This time slot is not available.'];
        }

        return ['available' => true, 'reason' => null];
    }

    /**
     * @param array<string, mixed> $data
     */
    public function createBooking(array $data): Booking
    {
        $location = Location::findOrFail($data['location_id']);
        $staff = isset($data['staff_member_id']) ? StaffMember::find($data['staff_member_id']) : null;

        $check = $this->checkAvailability(
            $location,
            Carbon::parse($data['appointment_datetime']),
            $data['duration_minutes'],
            $staff
        );

        if (! $check['available']) {
            throw new \RuntimeException($check['reason']);
        }

        return Booking::create($data);
    }
}
