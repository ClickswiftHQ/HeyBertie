<?php

namespace App\Http\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;

class StoreManualBookingRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'location_id' => ['required', 'integer', 'exists:locations,id'],
            'service_ids' => ['required', 'array', 'min:1'],
            'service_ids.*' => ['integer', 'exists:services,id'],
            'appointment_datetime' => ['required', 'date', 'after:now'],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'name' => ['required_without:customer_id', 'nullable', 'string', 'max:255'],
            'email' => ['required_without:customer_id', 'nullable', 'email', 'max:255'],
            'phone' => ['required_without:customer_id', 'nullable', 'string', 'max:30'],
            'pet_name' => ['required', 'string', 'max:255'],
            'pet_breed' => ['nullable', 'string', 'max:255'],
            'pet_size' => ['nullable', 'string', 'in:small,medium,large'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
