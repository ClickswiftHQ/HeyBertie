<?php

namespace App\Services;

use App\Models\Business;

class DepositCalculator
{
    /**
     * Calculate the deposit amount in pence.
     *
     * Returns 0 if deposits are disabled or the business can't accept payments.
     */
    public function calculateDeposit(Business $business, int $bookingTotalPence): int
    {
        $settings = $business->settings ?? [];

        if (! ($settings['deposits_enabled'] ?? false)) {
            return 0;
        }

        if (! $business->canAcceptPayments()) {
            return 0;
        }

        $type = $settings['deposit_type'] ?? 'fixed';

        if ($type === 'percentage') {
            $percentage = (int) ($settings['deposit_percentage'] ?? 0);

            return (int) round($bookingTotalPence * $percentage / 100);
        }

        // Fixed amount (stored in pence)
        $fixedAmount = (int) ($settings['deposit_fixed_amount'] ?? 0);

        // Don't charge more than the booking total
        return min($fixedAmount, $bookingTotalPence);
    }

    /**
     * Calculate the platform fee in pence (2.5% of the deposit).
     */
    public function calculatePlatformFee(int $depositAmountPence): int
    {
        return (int) round($depositAmountPence * 0.025);
    }
}
