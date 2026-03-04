<?php

namespace App\Http\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBusinessSettingsRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'auto_confirm_bookings' => ['required', 'boolean'],
            'staff_selection_enabled' => ['required', 'boolean'],
        ];
    }
}
