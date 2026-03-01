<?php

namespace App\Http\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;

class StoreAvailabilityBlockRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'day_of_week' => ['required_without:specific_date', 'nullable', 'integer', 'min:0', 'max:6'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'specific_date' => ['required_without:day_of_week', 'nullable', 'date', 'after_or_equal:today'],
            'block_type' => ['required', 'string', 'in:available,blocked,holiday,break'],
            'notes' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'start_time.required' => 'Please enter a start time.',
            'end_time.required' => 'Please enter an end time.',
            'end_time.after' => 'End time must be after start time.',
            'block_type.required' => 'Please select a block type.',
        ];
    }
}
