<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AdminUpdateSubscriptionRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'subscription_tier_id' => ['required', 'exists:subscription_tiers,id'],
            'subscription_status_id' => ['required', 'exists:subscription_statuses,id'],
        ];
    }
}
