<?php

namespace App\Http\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;

class StoreServiceRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'duration_minutes' => ['required', 'integer', 'min:5', 'max:480'],
            'price' => ['nullable', 'numeric', 'min:0', 'max:9999.99'],
            'price_type' => ['required', 'string', 'in:fixed,from,call'],
            'is_featured' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Please enter a service name.',
            'duration_minutes.required' => 'Please enter a duration.',
            'duration_minutes.min' => 'Duration must be at least 5 minutes.',
            'duration_minutes.max' => 'Duration cannot exceed 480 minutes.',
            'price.max' => 'Price cannot exceed £9,999.99.',
            'price_type.required' => 'Please select a price type.',
        ];
    }
}
