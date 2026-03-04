<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AdminUpdateSettingsRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'auto_confirm_bookings' => ['sometimes', 'boolean'],
            'deposits_enabled' => ['sometimes', 'boolean'],
            'deposit_type' => ['sometimes', 'string', 'in:fixed,percentage'],
            'deposit_fixed_amount' => ['sometimes', 'integer', 'min:0'],
            'deposit_percentage' => ['sometimes', 'integer', 'min:0', 'max:100'],
        ];
    }
}
