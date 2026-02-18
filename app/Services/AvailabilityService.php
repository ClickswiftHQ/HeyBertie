<?php

namespace App\Services;

use App\Models\AvailabilityBlock;
use App\Models\Booking;
use App\Models\Location;
use App\Models\StaffMember;
use Carbon\Carbon;

class AvailabilityService
{
    /**
     * @return list<array{time: string, duration: int}>
     */
    public function getAvailableSlots(Location $location, Carbon $date, ?StaffMember $staff = null, int $durationMinutes = 60): array
    {
        $blocks = AvailabilityBlock::query()
            ->where('business_id', $location->business_id)
            ->where(function ($q) use ($location) {
                $q->where('location_id', $location->id)->orWhereNull('location_id');
            })
            ->when($staff, function ($q) use ($staff) {
                $q->where(function ($q) use ($staff) {
                    $q->where('staff_member_id', $staff->id)->orWhereNull('staff_member_id');
                });
            })
            ->forDate($date)
            ->get();

        $availableBlocks = $blocks->where('block_type', 'available');
        $blockedBlocks = $blocks->whereIn('block_type', ['break', 'blocked', 'holiday']);

        $bookings = Booking::query()
            ->where('location_id', $location->id)
            ->whereDate('appointment_datetime', $date)
            ->whereNotIn('status', ['cancelled'])
            ->when($staff, fn ($q) => $q->where('staff_member_id', $staff->id))
            ->get();

        $slots = [];

        foreach ($availableBlocks as $block) {
            $startMinutes = $this->timeToMinutes($block->start_time);
            $endMinutes = $this->timeToMinutes($block->end_time);
            $bufferMinutes = $location->booking_buffer_minutes;

            for ($time = $startMinutes; $time + $durationMinutes <= $endMinutes; $time += 30) {
                $slotStart = $date->copy()->startOfDay()->addMinutes($time);
                $slotEnd = $slotStart->copy()->addMinutes($durationMinutes);

                if ($this->isBlockedAt($blockedBlocks, $slotStart, $slotEnd)) {
                    continue;
                }

                if ($this->hasConflictingBooking($bookings, $slotStart, $slotEnd, $bufferMinutes)) {
                    continue;
                }

                $slots[] = [
                    'time' => $slotStart->format('H:i'),
                    'duration' => $durationMinutes,
                ];
            }
        }

        return $slots;
    }

    public function isTimeSlotAvailable(Location $location, Carbon $datetime, int $duration, ?StaffMember $staff = null): bool
    {
        $slots = $this->getAvailableSlots($location, $datetime->copy()->startOfDay(), $staff, $duration);

        $requestedTime = $datetime->format('H:i');

        foreach ($slots as $slot) {
            if ($slot['time'] === $requestedTime) {
                return true;
            }
        }

        return false;
    }

    private function timeToMinutes(string $time): int
    {
        [$hours, $minutes] = explode(':', $time);

        return (int) $hours * 60 + (int) $minutes;
    }

    /**
     * @param \Illuminate\Support\Collection<int, AvailabilityBlock> $blockedBlocks
     */
    private function isBlockedAt($blockedBlocks, Carbon $start, Carbon $end): bool
    {
        foreach ($blockedBlocks as $block) {
            $blockStart = $start->copy()->startOfDay()->addMinutes($this->timeToMinutes($block->start_time));
            $blockEnd = $start->copy()->startOfDay()->addMinutes($this->timeToMinutes($block->end_time));

            if ($start->lt($blockEnd) && $end->gt($blockStart)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param \Illuminate\Support\Collection<int, Booking> $bookings
     */
    private function hasConflictingBooking($bookings, Carbon $start, Carbon $end, int $buffer): bool
    {
        foreach ($bookings as $booking) {
            $bookingStart = $booking->appointment_datetime;
            $bookingEnd = $bookingStart->copy()->addMinutes($booking->duration_minutes + $buffer);

            if ($start->lt($bookingEnd) && $end->gt($bookingStart)) {
                return true;
            }
        }

        return false;
    }
}
