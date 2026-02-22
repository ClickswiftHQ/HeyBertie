<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SearchRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'location' => ['required', 'string', 'max:100'],
            'service' => ['sometimes', 'string', 'in:dog-grooming,dog-walking,cat-sitting'],
            'sort' => ['sometimes', 'string', 'in:distance,rating,price_low,price_high'],
            'rating' => ['sometimes', 'integer', 'in:1,2,3,4,5'],
            'distance' => ['sometimes', 'integer', 'in:5,10,25,50'],
            'type' => ['sometimes', 'string', 'in:salon,mobile,home_based'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'location.required' => 'Please enter a location to search.',
        ];
    }
}
