<?php

namespace App\Services;

use App\Models\AvailabilityBlock;
use App\Models\Booking;
use App\Models\Location;
use App\Models\StaffMember;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AvailabilityService
{
    /**
     * @return list<array{time: string, duration: int}>
     */
    public function getAvailableSlots(Location $location, Carbon $date, ?StaffMember $staff = null, int $durationMinutes = 60): array
    {
        $blocks = $this->getBlocksForDate($location, $date, $staff);

        $availableBlocks = $blocks->where('block_type', 'available');
        $blockedBlocks = $blocks->whereIn('block_type', ['break', 'blocked', 'holiday']);

        $bookings = Booking::query()
            ->where('location_id', $location->id)
            ->whereDate('appointment_datetime', $date)
            ->whereNotIn('status', ['cancelled'])
            ->when($staff, fn ($q) => $q->where('staff_member_id', $staff->id))
            ->get();

        $bufferMinutes = $location->booking_buffer_minutes;
        $slots = [];

        foreach ($availableBlocks as $block) {
            $startMinutes = $this->timeToMinutes($block->start_time);
            $endMinutes = $this->timeToMinutes($block->end_time);

            for ($time = $startMinutes; $time + $durationMinutes <= $endMinutes; $time += 30) {
                $slotStart = $date->copy()->startOfDay()->addMinutes($time);
                $slotEnd = $slotStart->copy()->addMinutes($durationMinutes);

                if ($this->isBlockedAt($blockedBlocks, $slotStart, $slotEnd)) {
                    continue;
                }

                if ($this->collectionHasConflict($bookings, $slotStart, $slotEnd, $bufferMinutes)) {
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

    /**
     * Get dates that have at least one available slot within the booking window.
     *
     * @return list<array{date: string, available: bool}>
     */
    public function getAvailableDates(Location $location, ?StaffMember $staff, int $durationMinutes, int $daysAhead): array
    {
        $dates = [];
        $today = Carbon::today();

        for ($i = 0; $i < $daysAhead; $i++) {
            $date = $today->copy()->addDays($i);

            $slots = $this->getAvailableSlots($location, $date, $staff, $durationMinutes);

            // Filter out slots that don't meet the minimum notice requirement
            if ($date->isToday()) {
                $minNotice = now()->addHours($location->min_notice_hours);
                $slots = array_filter($slots, function ($slot) use ($date, $minNotice) {
                    $slotTime = $date->copy()->setTimeFromTimeString($slot['time']);

                    return $slotTime->gte($minNotice);
                });
            }

            $dates[] = [
                'date' => $date->toDateString(),
                'available' => count($slots) > 0,
            ];
        }

        return $dates;
    }

    public function isTimeSlotAvailable(Location $location, Carbon $datetime, int $duration, ?StaffMember $staff = null, ?int $excludeBookingId = null): bool
    {
        $date = $datetime->copy()->startOfDay();
        $slotTime = $datetime->format('H:i');
        $slotEndTime = $datetime->copy()->addMinutes($duration)->format('H:i');
        $buffer = $location->booking_buffer_minutes;

        $blockQuery = AvailabilityBlock::query()
            ->where('business_id', $location->business_id)
            ->where(function ($q) use ($location) {
                $q->where('location_id', $location->id)->orWhereNull('location_id');
            })
            ->when($staff, function ($q) use ($staff) {
                $q->where(function ($q) use ($staff) {
                    $q->where('staff_member_id', $staff->id)->orWhereNull('staff_member_id');
                });
            })
            ->forDate($date);

        $hasAvailableBlock = (clone $blockQuery)
            ->where('block_type', 'available')
            ->where('start_time', '<=', $slotTime)
            ->where('end_time', '>=', $slotEndTime)
            ->exists();

        if (! $hasAvailableBlock) {
            return false;
        }

        $isBlocked = (clone $blockQuery)
            ->whereIn('block_type', ['break', 'blocked', 'holiday'])
            ->where('start_time', '<', $slotEndTime)
            ->where('end_time', '>', $slotTime)
            ->exists();

        if ($isBlocked) {
            return false;
        }

        $slotStart = $datetime;
        $slotEnd = $datetime->copy()->addMinutes($duration);

        return ! $this->queryHasConflict($location, $slotStart, $slotEnd, $buffer, $staff, $excludeBookingId);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, AvailabilityBlock>
     */
    private function getBlocksForDate(Location $location, Carbon $date, ?StaffMember $staff = null): \Illuminate\Database\Eloquent\Collection
    {
        return AvailabilityBlock::query()
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
    }

    private function timeToMinutes(string $time): int
    {
        [$hours, $minutes] = explode(':', $time);

        return (int) $hours * 60 + (int) $minutes;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, AvailabilityBlock>  $blockedBlocks
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
     * Check booking conflicts using a pre-fetched collection (for getAvailableSlots).
     *
     * @param  \Illuminate\Support\Collection<int, Booking>  $bookings
     */
    private function collectionHasConflict($bookings, Carbon $start, Carbon $end, int $buffer): bool
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

    /**
     * Check booking conflicts using a database interval query (for single-slot checks).
     */
    private function queryHasConflict(Location $location, Carbon $start, Carbon $end, int $buffer, ?StaffMember $staff, ?int $excludeBookingId = null): bool
    {
        $driver = DB::connection()->getDriverName();

        $query = Booking::query()
            ->where('location_id', $location->id)
            ->whereNotIn('status', ['cancelled'])
            ->when($staff, fn ($q) => $q->where('staff_member_id', $staff->id))
            ->when($excludeBookingId, fn ($q) => $q->where('id', '!=', $excludeBookingId))
            ->where('appointment_datetime', '<', $end);

        if ($driver === 'sqlite') {
            $query->whereRaw(
                "datetime(appointment_datetime, '+' || (duration_minutes + ?) || ' minutes') > ?",
                [$buffer, $start->toDateTimeString()]
            );
        } else {
            $query->whereRaw(
                "appointment_datetime + ((duration_minutes + ?) * interval '1 minute') > ?",
                [$buffer, $start->toDateTimeString()]
            );
        }

        return $query->exists();
    }
}
