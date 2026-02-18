<?php

namespace App\Rules;

use App\Models\Business;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidHandle implements ValidationRule
{
    /** @var list<string> */
    protected const RESERVED_WORDS = [
        'admin',
        'api',
        'app',
        'dashboard',
        'cp',
        'search',
        'login',
        'register',
        'terms',
        'privacy',
        'help',
        'support',
        'about',
        'contact',
        'blog',
        'pricing',
    ];

    public function __construct(
        protected ?int $ignoreBusinessId = null
    ) {}

    /**
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('The :attribute must be a string.');

            return;
        }

        if (strlen($value) < 3 || strlen($value) > 30) {
            $fail('The :attribute must be between 3 and 30 characters.');

            return;
        }

        if (! preg_match('/^[a-z0-9][a-z0-9-]*[a-z0-9]$/', $value)) {
            $fail('The :attribute must be lowercase, contain only letters, numbers, and hyphens, and cannot start or end with a hyphen.');

            return;
        }

        if (str_contains($value, '--')) {
            $fail('The :attribute cannot contain consecutive hyphens.');

            return;
        }

        if (in_array($value, self::RESERVED_WORDS, true)) {
            $fail('The :attribute ":input" is reserved and cannot be used.');

            return;
        }

        $query = Business::query()->where('handle', $value);

        if ($this->ignoreBusinessId) {
            $query->where('id', '!=', $this->ignoreBusinessId);
        }

        if ($query->exists()) {
            $fail('The :attribute ":input" is already taken.');
        }
    }
}
