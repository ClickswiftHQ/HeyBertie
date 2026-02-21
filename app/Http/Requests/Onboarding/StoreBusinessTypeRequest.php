<?php

namespace App\Http\Requests\Onboarding;

use Illuminate\Foundation\Http\FormRequest;

class StoreBusinessTypeRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'business_type' => ['required', 'string', 'in:salon,mobile,home_based,hybrid'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'business_type.required' => 'Please select a business type.',
            'business_type.in' => 'Please select a valid business type.',
        ];
    }
}
