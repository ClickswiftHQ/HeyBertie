<?php

namespace App\Http\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBookingNotesRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'pro_notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
