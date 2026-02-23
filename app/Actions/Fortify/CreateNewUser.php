<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Exceptions\RegistrationEmailTakenException;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => $this->passwordRules(),
        ])->validate();

        $email = strtolower($input['email']);
        $existing = User::whereRaw('LOWER(email) = ?', [$email])->first();

        if (! $existing) {
            return User::create([
                'name' => $input['name'],
                'email' => $email,
                'password' => $input['password'],
                'is_registered' => true,
            ]);
        }

        if (! $existing->is_registered) {
            $existing->update([
                'name' => $input['name'],
                'password' => Hash::make($input['password']),
                'is_registered' => true,
                'email_verified_at' => null,
            ]);

            return $existing;
        }

        throw new RegistrationEmailTakenException;
    }
}
