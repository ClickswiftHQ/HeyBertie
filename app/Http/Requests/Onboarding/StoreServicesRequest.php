<?php

namespace App\Http\Requests\Onboarding;

use Illuminate\Foundation\Http\FormRequest;

class StoreServicesRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'services' => ['required', 'array', 'min:1'],
            'services.*.name' => ['required', 'string', 'max:255'],
            'services.*.description' => ['nullable', 'string', 'max:500'],
            'services.*.duration_minutes' => ['required', 'integer', 'min:5', 'max:480'],
            'services.*.price' => ['nullable', 'numeric', 'min:0', 'max:9999.99'],
            'services.*.price_type' => ['required', 'string', 'in:fixed,from,call'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'services.required' => 'Please add at least one service.',
            'services.min' => 'Please add at least one service.',
            'services.*.name.required' => 'Each service must have a name.',
            'services.*.duration_minutes.required' => 'Each service must have a duration.',
            'services.*.duration_minutes.min' => 'Duration must be at least 5 minutes.',
            'services.*.price_type.required' => 'Each service must have a price type.',
        ];
    }
}
