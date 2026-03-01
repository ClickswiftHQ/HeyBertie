<?php

namespace App\Http\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;

class ReorderServicesRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'order' => ['required', 'array'],
            'order.*' => ['integer', 'exists:services,id'],
        ];
    }
}
