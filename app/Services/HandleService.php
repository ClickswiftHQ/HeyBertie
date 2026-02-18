<?php

namespace App\Services;

use App\Models\Business;
use App\Models\HandleChange;
use App\Models\User;
use App\Rules\ValidHandle;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class HandleService
{
    /**
     * @throws ValidationException
     * @throws \RuntimeException
     */
    public function changeHandle(Business $business, string $newHandle, User $changedBy): void
    {
        $lastChange = $business->handleChanges()->latest('changed_at')->first();

        if ($lastChange && $lastChange->changed_at->diffInDays(now()) < 30) {
            throw new \RuntimeException('Handle can only be changed once every 30 days.');
        }

        $validator = Validator::make(
            ['handle' => $newHandle],
            ['handle' => ['required', 'string', new ValidHandle($business->id)]]
        );

        $validator->validate();

        $oldHandle = $business->handle;

        HandleChange::create([
            'business_id' => $business->id,
            'old_handle' => $oldHandle,
            'new_handle' => $newHandle,
            'changed_by_user_id' => $changedBy->id,
            'changed_at' => now(),
        ]);

        $business->update(['handle' => $newHandle]);
    }

    /**
     * @return list<string>
     */
    public function suggestAlternatives(string $desiredHandle): array
    {
        $suggestions = [];
        $suffixes = ['-uk', '-pro', '-grooming', '-pets', '-studio'];

        foreach ($suffixes as $suffix) {
            $candidate = Str::limit($desiredHandle . $suffix, 30, '');

            if (! Business::where('handle', $candidate)->exists()) {
                $suggestions[] = $candidate;
            }

            if (count($suggestions) >= 5) {
                break;
            }
        }

        $counter = 1;
        while (count($suggestions) < 5 && $counter <= 99) {
            $candidate = Str::limit($desiredHandle . '-' . $counter, 30, '');

            if (! Business::where('handle', $candidate)->exists()) {
                $suggestions[] = $candidate;
            }

            $counter++;
        }

        return array_slice($suggestions, 0, 5);
    }
}
