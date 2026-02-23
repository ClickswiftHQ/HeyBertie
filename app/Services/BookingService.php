<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Customer;
use App\Models\Location;
use App\Models\Service;
use App\Models\StaffMember;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BookingService
{
    public function __construct(
        private AvailabilityService $availabilityService
    ) {}

    /**
     * @return array{available: bool, reason: string|null}
     */
    public function checkAvailability(Location $location, Carbon $start, int $duration, ?StaffMember $staff = null, ?int $excludeBookingId = null): array
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

        if (! $this->availabilityService->isTimeSlotAvailable($location, $start, $duration, $staff, $excludeBookingId)) {
            return ['available' => false, 'reason' => 'This time slot is not available.'];
        }

        return ['available' => true, 'reason' => null];
    }

    public function rescheduleBooking(Booking $booking, Carbon $newDatetime): Booking
    {
        if (! $booking->canBeRescheduled()) {
            throw new \RuntimeException('This booking cannot be rescheduled.');
        }

        $booking->loadMissing(['location', 'staffMember']);

        $check = $this->checkAvailability(
            $booking->location,
            $newDatetime,
            $booking->duration_minutes,
            $booking->staffMember,
            $booking->id,
        );

        if (! $check['available']) {
            throw new \RuntimeException($check['reason']);
        }

        $booking->update(['appointment_datetime' => $newDatetime]);

        return $booking->fresh();
    }

    /**
     * @param  array<string, mixed>  $data
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

    /**
     * Create a multi-service booking with itemised breakdown.
     *
     * @param  array{
     *     location_id: int,
     *     service_ids: list<int>,
     *     staff_member_id: int|null,
     *     appointment_datetime: string,
     *     name: string,
     *     email: string,
     *     phone: string,
     *     pet_name: string,
     *     pet_breed: string|null,
     *     pet_size: string|null,
     *     notes: string|null,
     * }  $data
     */
    public function createMultiServiceBooking(array $data): Booking
    {
        $location = Location::findOrFail($data['location_id']);
        $services = Service::query()
            ->where('business_id', $location->business_id)
            ->where('is_active', true)
            ->whereIn('id', $data['service_ids'])
            ->forLocation($location)
            ->get();

        if ($services->isEmpty()) {
            throw new \RuntimeException('No valid services selected.');
        }

        $totalDuration = $services->sum('duration_minutes');
        $totalPrice = $services->sum(fn (Service $s) => (float) $s->price);
        $staff = isset($data['staff_member_id']) ? StaffMember::find($data['staff_member_id']) : null;
        $appointmentDatetime = Carbon::parse($data['appointment_datetime']);

        return DB::transaction(function () use ($location, $services, $staff, $appointmentDatetime, $totalDuration, $totalPrice, $data) {
            $check = $this->checkAvailability($location, $appointmentDatetime, $totalDuration, $staff);

            if (! $check['available']) {
                throw new \RuntimeException($check['reason']);
            }

            $user = $this->findOrCreateUser($data['name'], $data['email']);
            $customer = $this->findOrCreateCustomer($location->business_id, $user, $data['phone']);

            $booking = Booking::create([
                'business_id' => $location->business_id,
                'location_id' => $location->id,
                'service_id' => $services->count() === 1 ? $services->first()->id : null,
                'customer_id' => $customer->id,
                'staff_member_id' => $staff?->id,
                'appointment_datetime' => $appointmentDatetime,
                'duration_minutes' => $totalDuration,
                'status' => 'pending',
                'booking_reference' => Booking::generateReference(),
                'price' => $totalPrice,
                'payment_status' => 'pending',
                'customer_notes' => $data['notes'] ?? null,
                'pet_name' => $data['pet_name'],
                'pet_breed' => $data['pet_breed'] ?? null,
                'pet_size' => $data['pet_size'] ?? null,
            ]);

            foreach ($services->values() as $index => $service) {
                BookingItem::create([
                    'booking_id' => $booking->id,
                    'service_id' => $service->id,
                    'service_name' => $service->name,
                    'duration_minutes' => $service->duration_minutes,
                    'price' => $service->price ?? 0,
                    'display_order' => $index,
                ]);
            }

            return $booking->load('items');
        });
    }

    private function findOrCreateUser(string $name, string $email): User
    {
        $email = strtolower($email);
        $user = User::whereRaw('LOWER(email) = ?', [$email])->first();

        if ($user) {
            return $user;
        }

        return User::create([
            'name' => $name,
            'email' => $email,
            'password' => bcrypt(str()->random(32)),
            'is_registered' => false,
        ]);
    }

    private function findOrCreateCustomer(int $businessId, User $user, string $phone): Customer
    {
        return Customer::firstOrCreate(
            ['business_id' => $businessId, 'user_id' => $user->id],
            [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $phone,
                'source' => 'online',
            ]
        );
    }
}
