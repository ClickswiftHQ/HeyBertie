<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'service_ids' => ['required', 'array', 'min:1'],
            'service_ids.*' => ['required', 'integer', 'exists:services,id'],
            'staff_member_id' => ['nullable', 'integer', 'exists:staff_members,id'],
            'appointment_datetime' => ['required', 'date', 'after:now'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'pet_name' => ['required', 'string', 'max:255'],
            'pet_breed' => ['nullable', 'string', 'max:255'],
            'pet_size' => ['nullable', 'string', 'in:small,medium,large'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'service_ids.required' => 'Please select at least one service.',
            'service_ids.min' => 'Please select at least one service.',
            'appointment_datetime.required' => 'Please select a date and time.',
            'appointment_datetime.after' => 'The appointment must be in the future.',
            'name.required' => 'Please enter your name.',
            'email.required' => 'Please enter your email address.',
            'phone.required' => 'Please enter your phone number.',
            'pet_name.required' => 'Please enter your pet\'s name.',
        ];
    }
}
