<?php

namespace App\Services;

use App\Models\Business;
use App\Models\SmsLog;
use Carbon\Carbon;

class SmsQuotaService
{
    /** @var array<string, float> */
    private const OVERAGE_RATES = [
        'solo' => 0.06,
        'salon' => 0.05,
    ];

    public function getRemainingQuota(Business $business, ?Carbon $month = null): int
    {
        $month = $month ?? now();
        $quota = $business->subscriptionTier->sms_quota;
        $sent = $this->getSentCount($business, $month);

        return max(0, $quota - $sent);
    }

    public function canSendSms(Business $business): bool
    {
        if ($business->subscriptionTier->slug === 'free') {
            return false;
        }

        return true; // Paid tiers can always send (overage billed)
    }

    public function calculateOverageCharge(Business $business, ?Carbon $month = null): float
    {
        $month = $month ?? now();
        $quota = $business->subscriptionTier->sms_quota;
        $sent = $this->getSentCount($business, $month);
        $overage = max(0, $sent - $quota);
        $rate = self::OVERAGE_RATES[$business->subscriptionTier->slug] ?? 0;

        return round($overage * $rate, 2);
    }

    private function getSentCount(Business $business, Carbon $month): int
    {
        return SmsLog::query()
            ->where('business_id', $business->id)
            ->whereYear('created_at', $month->year)
            ->whereMonth('created_at', $month->month)
            ->whereIn('status', ['sent', 'delivered'])
            ->count();
    }
}
