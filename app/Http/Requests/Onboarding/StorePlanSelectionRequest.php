<?php

namespace App\Http\Requests\Onboarding;

use Illuminate\Foundation\Http\FormRequest;

class StorePlanSelectionRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'tier' => ['required', 'string', 'in:free,solo,salon'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tier.required' => 'Please select a plan.',
            'tier.in' => 'Please select a valid plan.',
        ];
    }
}
