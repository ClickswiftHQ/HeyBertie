import { usePage } from '@inertiajs/react';
import { AlertCircle, Clock, Info } from 'lucide-react';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';

export function SubscriptionBanner() {
    const { currentBusiness } = usePage().props;

    if (!currentBusiness) {
        return null;
    }

    const {
        subscription_tier,
        has_active_subscription,
        on_trial,
        trial_days_remaining,
        handle,
    } = currentBusiness;

    // Active subscriber (not on trial) — no banner needed
    if (has_active_subscription && !on_trial) {
        return null;
    }

    // Free tier
    if (subscription_tier === 'free') {
        return (
            <Alert className="border-blue-200 bg-blue-50 text-blue-900 dark:border-blue-800 dark:bg-blue-950 dark:text-blue-100">
                <Info className="text-blue-600 dark:text-blue-400" />
                <AlertTitle>Want to accept bookings online?</AlertTitle>
                <AlertDescription>
                    Upgrade to a paid plan to unlock booking, scheduling,
                    and customer management features.
                </AlertDescription>
            </Alert>
        );
    }

    // On trial
    if (on_trial && trial_days_remaining !== null) {
        const isUrgent = trial_days_remaining <= 3;
        const daysLabel =
            trial_days_remaining === 1 ? '1 day' : `${trial_days_remaining} days`;

        return (
            <Alert
                className={
                    isUrgent
                        ? 'border-amber-200 bg-amber-50 text-amber-900 dark:border-amber-800 dark:bg-amber-950 dark:text-amber-100'
                        : 'border-blue-200 bg-blue-50 text-blue-900 dark:border-blue-800 dark:bg-blue-950 dark:text-blue-100'
                }
            >
                <Clock
                    className={
                        isUrgent
                            ? 'text-amber-600 dark:text-amber-400'
                            : 'text-blue-600 dark:text-blue-400'
                    }
                />
                <AlertTitle>{daysLabel} left in your free trial</AlertTitle>
                <AlertDescription>
                    <span>
                        Subscribe now to keep your booking calendar, customer
                        management, and reminders active.
                    </span>{' '}
                    <a
                        href={`/${handle}/subscription/checkout`}
                        className="font-medium underline underline-offset-2"
                    >
                        Subscribe now
                    </a>
                </AlertDescription>
            </Alert>
        );
    }

    // Trial expired / no active subscription (paid tier)
    return (
        <Alert className="border-red-200 bg-red-50 text-red-900 dark:border-red-800 dark:bg-red-950 dark:text-red-100">
            <AlertCircle className="text-red-600 dark:text-red-400" />
            <AlertTitle>Your trial has ended</AlertTitle>
            <AlertDescription>
                <span>
                    Subscribe to restore access to your booking calendar,
                    customer management, and reminders.
                </span>{' '}
                <Link
                    href={`/${handle}/subscription/checkout`}
                    className="font-medium underline underline-offset-2"
                >
                    Subscribe now
                </Link>
            </AlertDescription>
        </Alert>
    );
}
