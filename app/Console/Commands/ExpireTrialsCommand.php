<?php

namespace App\Console\Commands;

use App\Models\Business;
use App\Models\SubscriptionStatus;
use App\Models\SubscriptionTier;
use Illuminate\Console\Command;

class ExpireTrialsCommand extends Command
{
    protected $signature = 'subscriptions:expire-trials';

    protected $description = 'Downgrade businesses whose trials have expired and have no active Cashier subscription';

    public function handle(): int
    {
        $trialStatus = SubscriptionStatus::findBySlug('trial');
        $cancelledStatus = SubscriptionStatus::findBySlug('cancelled');
        $freeTier = SubscriptionTier::findBySlug('free');

        if (! $trialStatus || ! $cancelledStatus || ! $freeTier) {
            $this->error('Required subscription statuses or tiers not found.');

            return self::FAILURE;
        }

        $expiredBusinesses = Business::query()
            ->where('subscription_status_id', $trialStatus->id)
            ->where('trial_ends_at', '<=', now())
            ->get();

        $count = 0;

        foreach ($expiredBusinesses as $business) {
            // Skip businesses that have an active Cashier subscription
            if ($business->subscribed('default')) {
                continue;
            }

            $business->update([
                'subscription_tier_id' => $freeTier->id,
                'subscription_status_id' => $cancelledStatus->id,
            ]);

            $count++;
        }

        $this->info("Expired {$count} trial(s).");

        return self::SUCCESS;
    }
}
