<?php

namespace App\Http\Requests\Onboarding;

use App\Rules\ValidHandle;
use Illuminate\Foundation\Http\FormRequest;

class StoreHandleRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'handle' => ['required', 'string', 'min:3', 'max:30', new ValidHandle],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'handle.required' => 'Please choose a handle for your business.',
            'handle.min' => 'Handle must be at least 3 characters.',
            'handle.max' => 'Handle must be no more than 30 characters.',
        ];
    }
}
