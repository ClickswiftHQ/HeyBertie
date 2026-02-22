<?php

namespace App\Http\Requests\Onboarding;

use App\Support\PostcodeFormatter;
use Illuminate\Foundation\Http\FormRequest;

class StoreLocationRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('postcode') && $this->postcode) {
            $this->merge([
                'postcode' => PostcodeFormatter::format($this->postcode),
            ]);
        }
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'location_type' => ['nullable', 'string', 'in:salon,mobile,home_based'],
            'address_line_1' => ['required', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'town' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'postcode' => ['required', 'string', 'regex:/^[A-Z]{1,2}[0-9][A-Z0-9]?\s?[0-9][A-Z]{2}$/i'],
            'county' => ['nullable', 'string', 'max:255'],
            'service_radius_km' => ['nullable', 'integer', 'min:1', 'max:100'],
            'phone' => ['nullable', 'string', 'regex:/^(\+44|0)[0-9\s\-]{9,13}$/'],
            'email' => ['nullable', 'email', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'address_line_1.required' => 'Please enter your address.',
            'town.required' => 'Please enter your town or area.',
            'city.required' => 'Please enter your city.',
            'postcode.required' => 'Please enter your postcode.',
            'postcode.regex' => 'Please enter a valid UK postcode.',
            'phone.regex' => 'Please enter a valid UK phone number.',
        ];
    }
}
