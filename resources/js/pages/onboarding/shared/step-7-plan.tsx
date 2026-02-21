import { Form, Head } from '@inertiajs/react';
import { CheckIcon } from 'lucide-react';
import { useState } from 'react';
import StepNavigation from '@/components/onboarding/step-navigation';
import { Badge } from '@/components/ui/badge';
import OnboardingLayout from '@/layouts/onboarding-layout';
import { cn } from '@/lib/utils';
import { store } from '@/routes/onboarding';

interface Plan {
    tier: string;
    name: string;
    price: number;
    features: string[];
    highlighted: boolean;
    cta: string;
}

interface Step7Props {
    step: number;
    totalSteps: number;
    completedSteps: number[];
    selectedTier: string | null;
    plans: Plan[];
}

export default function Step7Plan({
    step,
    totalSteps,
    completedSteps,
    selectedTier,
    plans,
}: Step7Props) {
    const [selected, setSelected] = useState(selectedTier ?? '');

    return (
        <OnboardingLayout
            step={step}
            totalSteps={totalSteps}
            completedSteps={completedSteps}
            title="Choose your plan"
            description="Start free or unlock powerful features with a paid plan."
        >
            <Head title="Plan Selection - Onboarding" />
            <Form
                action={store.url(step)}
                method="post"
                className="space-y-6"
            >
                {({ processing, errors }) => (
                    <>
                        <input type="hidden" name="tier" value={selected} />

                        <div className="grid gap-4 md:grid-cols-3">
                            {plans.map((plan) => {
                                const isSelected = selected === plan.tier;

                                return (
                                    <button
                                        key={plan.tier}
                                        type="button"
                                        onClick={() =>
                                            setSelected(plan.tier)
                                        }
                                        className={cn(
                                            'relative flex flex-col rounded-xl border-2 p-6 text-left transition-colors',
                                            isSelected
                                                ? 'border-primary bg-primary/5'
                                                : 'border-border hover:border-primary/50',
                                            plan.highlighted &&
                                                !isSelected &&
                                                'border-primary/30',
                                        )}
                                    >
                                        {plan.highlighted && (
                                            <Badge className="absolute -top-2.5 right-4">
                                                Most Popular
                                            </Badge>
                                        )}

                                        <div className="mb-4">
                                            <h3 className="text-lg font-semibold">
                                                {plan.name}
                                            </h3>
                                            <div className="mt-2">
                                                <span className="text-3xl font-bold">
                                                    &pound;{plan.price}
                                                </span>
                                                {plan.price > 0 && (
                                                    <span className="text-sm text-muted-foreground">
                                                        /month
                                                    </span>
                                                )}
                                            </div>
                                        </div>

                                        <ul className="flex-1 space-y-2">
                                            {plan.features.map((feature) => (
                                                <li
                                                    key={feature}
                                                    className="flex items-start gap-2 text-sm"
                                                >
                                                    <CheckIcon className="mt-0.5 size-4 shrink-0 text-primary" />
                                                    {feature}
                                                </li>
                                            ))}
                                        </ul>

                                        <div className="mt-4 text-center text-sm font-medium text-primary">
                                            {plan.cta}
                                        </div>
                                    </button>
                                );
                            })}
                        </div>

                        <p className="text-center text-sm text-muted-foreground">
                            All paid plans include a 14-day free trial. You can
                            upgrade or downgrade anytime.
                        </p>

                        {errors.tier && (
                            <p className="text-sm text-red-600 dark:text-red-400">
                                {errors.tier}
                            </p>
                        )}

                        <StepNavigation
                            currentStep={step}
                            totalSteps={totalSteps}
                            processing={processing}
                            nextDisabled={!selected}
                        />
                    </>
                )}
            </Form>
        </OnboardingLayout>
    );
}
