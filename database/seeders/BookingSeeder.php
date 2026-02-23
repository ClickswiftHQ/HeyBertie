<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Business;
use App\Models\SubscriptionTier;
use Illuminate\Database\Seeder;

class BookingSeeder extends Seeder
{
    public function run(): void
    {
        $paidTierIds = SubscriptionTier::whereIn('slug', ['solo', 'salon'])->pluck('id');

        $businesses = Business::whereIn('subscription_tier_id', $paidTierIds)
            ->with(['locations', 'services', 'customers', 'staffMembers'])
            ->get();

        foreach ($businesses as $business) {
            $locations = $business->locations;
            $services = $business->services;
            $customers = $business->customers;
            $staffMembers = $business->staffMembers;

            if ($locations->isEmpty() || $services->isEmpty() || $customers->isEmpty()) {
                continue;
            }

            $bookingCount = fake()->numberBetween(20, 40);

            for ($i = 0; $i < $bookingCount; $i++) {
                $location = $locations->random();
                $selectedServices = $services->random(fake()->numberBetween(1, min(3, $services->count())));
                $selectedServices = $selectedServices instanceof \Illuminate\Support\Collection ? $selectedServices : collect([$selectedServices]);
                $customer = $customers->random();
                $staff = $staffMembers->isNotEmpty() ? $staffMembers->random() : null;

                $isPast = fake()->boolean(60);
                $appointmentDate = $isPast
                    ? fake()->dateTimeBetween('-3 months', '-1 day')
                    : fake()->dateTimeBetween('+1 day', '+30 days');

                // Set time to business hours
                $hour = fake()->numberBetween(9, 16);
                $minute = fake()->randomElement([0, 30]);
                $appointmentDate->setTime($hour, $minute);

                $status = $isPast
                    ? fake()->randomElement(['completed', 'completed', 'completed', 'completed', 'no_show', 'cancelled'])
                    : fake()->randomElement(['confirmed', 'confirmed', 'confirmed', 'pending']);

                $totalDuration = $selectedServices->sum('duration_minutes');
                $totalPrice = $selectedServices->sum(fn ($s) => $s->price ?? fake()->randomFloat(2, 25, 60));

                $booking = Booking::create([
                    'business_id' => $business->id,
                    'location_id' => $location->id,
                    'customer_id' => $customer->id,
                    'staff_member_id' => $staff?->id,
                    'appointment_datetime' => $appointmentDate,
                    'duration_minutes' => $totalDuration,
                    'status' => $status,
                    'price' => $totalPrice,
                    'deposit_amount' => fake()->boolean(30) ? 10.00 : 0,
                    'deposit_paid' => fake()->boolean(20),
                    'payment_status' => $status === 'completed' ? 'paid' : 'pending',
                    'customer_notes' => fake()->boolean(30) ? fake()->sentence() : null,
                    'pro_notes' => fake()->boolean(20) ? fake()->sentence() : null,
                    'cancelled_at' => $status === 'cancelled' ? now() : null,
                    'cancellation_reason' => $status === 'cancelled' ? fake()->sentence() : null,
                ]);

                foreach ($selectedServices->values() as $index => $service) {
                    BookingItem::create([
                        'booking_id' => $booking->id,
                        'service_id' => $service->id,
                        'service_name' => $service->name,
                        'duration_minutes' => $service->duration_minutes,
                        'price' => $service->price ?? 0,
                        'display_order' => $index,
                    ]);
                }

                // Update customer stats for completed bookings
                if ($status === 'completed') {
                    $customer->updateFromBooking($booking);
                }
            }
        }
    }
}
