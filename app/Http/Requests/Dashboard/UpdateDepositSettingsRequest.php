<?php

namespace App\Http\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDepositSettingsRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'deposits_enabled' => ['required', 'boolean'],
            'deposit_type' => ['required_if:deposits_enabled,true', 'string', 'in:fixed,percentage'],
            'deposit_fixed_amount' => ['required_if:deposit_type,fixed', 'nullable', 'numeric', 'min:0.50', 'max:9999.99'],
            'deposit_percentage' => ['required_if:deposit_type,percentage', 'nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'deposit_fixed_amount.required_if' => 'Please enter a deposit amount.',
            'deposit_fixed_amount.min' => 'Deposit must be at least £0.50.',
            'deposit_percentage.required_if' => 'Please enter a deposit percentage.',
            'deposit_percentage.min' => 'Deposit percentage must be at least 1%.',
            'deposit_percentage.max' => 'Deposit percentage cannot exceed 100%.',
        ];
    }
}
