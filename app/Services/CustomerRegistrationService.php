<?php

namespace App\Services;

use App\Models\Business;
use App\Models\BusinessPet;
use App\Models\Customer;
use App\Models\Pet;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Throwable;

class CustomerRegistrationService
{
    /**
     * Find or create a customer for a business.
     *
     * - User with email exists + customer at this business -> return existing customer
     * - User with email exists, no customer here -> create customer linking to existing user
     * - No user -> create stub user (is_registered=false) + customer
     *
     * @param  array<string, mixed>  $data  Must contain 'email' and 'name', may contain 'phone', 'address', 'source'
     *
     * @throws Throwable
     */
    public function findOrCreateForBusiness(Business $business, array $data): Customer
    {
        return DB::transaction(function () use ($business, $data) {
            $user = User::where('email', $data['email'])->first();

            if ($user) {
                $existingCustomer = Customer::where('business_id', $business->id)
                    ->where('user_id', $user->id)
                    ->first();

                if ($existingCustomer) {
                    return $existingCustomer;
                }

                return Customer::create([
                    'business_id' => $business->id,
                    'user_id' => $user->id,
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'phone' => $data['phone'] ?? null,
                    'address' => $data['address'] ?? null,
                    'source' => $data['source'] ?? 'online',
                ]);
            }

            $stubUser = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make(str()->random(32)),
                'is_registered' => false,
            ]);

            return Customer::create([
                'business_id' => $business->id,
                'user_id' => $stubUser->id,
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
                'source' => $data['source'] ?? 'online',
            ]);
        });
    }

    /**
     * Upgrade a stub user to a fully registered user.
     *
     * @param  array<string, mixed>  $registrationData  Must contain 'password', may contain 'name'
     *
     * @throws RuntimeException
     */
    public function upgradeStubUser(User $user, array $registrationData): User
    {
        if ($user->is_registered) {
            throw new RuntimeException('User is already registered.');
        }

        $user->update([
            'name' => $registrationData['name'] ?? $user->name,
            'password' => Hash::make($registrationData['password']),
            'is_registered' => true,
            'email_verified_at' => now(),
        ]);

        return $user->refresh();
    }

    /**
     * Link a pet to a business with optional notes.
     *
     * @param  array<string, mixed>  $notes  May contain 'notes', 'difficulty_rating'
     */
    public function linkPetToBusiness(Pet $pet, Business $business, array $notes = []): BusinessPet
    {
        return BusinessPet::updateOrCreate(
            [
                'business_id' => $business->id,
                'pet_id' => $pet->id,
            ],
            [
                'notes' => $notes['notes'] ?? null,
                'difficulty_rating' => $notes['difficulty_rating'] ?? null,
                'last_seen_at' => now(),
            ],
        );
    }
}
