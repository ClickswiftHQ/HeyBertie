<?php

namespace App\Http\Requests\Onboarding;

use Illuminate\Foundation\Http\FormRequest;

class StoreVerificationRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'photo_id' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'qualification' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'insurance' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'photo_id.mimes' => 'Photo ID must be a JPG, PNG, or PDF file.',
            'photo_id.max' => 'Photo ID must be less than 5MB.',
            'qualification.mimes' => 'Qualification must be a JPG, PNG, or PDF file.',
            'qualification.max' => 'Qualification must be less than 5MB.',
            'insurance.mimes' => 'Insurance document must be a JPG, PNG, or PDF file.',
            'insurance.max' => 'Insurance document must be less than 5MB.',
        ];
    }
}
