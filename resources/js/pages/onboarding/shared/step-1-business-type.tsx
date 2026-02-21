import { Form, Head } from '@inertiajs/react';
import { BuildingIcon, CarIcon, HomeIcon, LayersIcon } from 'lucide-react';
import { useState } from 'react';
import StepNavigation from '@/components/onboarding/step-navigation';
import OnboardingLayout from '@/layouts/onboarding-layout';
import { store } from '@/routes/onboarding';

const BUSINESS_TYPES = [
    {
        value: 'salon',
        label: 'Salon',
        description: 'Fixed location grooming salon',
        icon: BuildingIcon,
    },
    {
        value: 'mobile',
        label: 'Mobile',
        description: "Travel to customers' homes",
        icon: CarIcon,
    },
    {
        value: 'home_based',
        label: 'Home-based',
        description: 'Groom from your own home',
        icon: HomeIcon,
    },
    {
        value: 'hybrid',
        label: 'Hybrid',
        description: 'Combination of salon + mobile',
        icon: LayersIcon,
    },
] as const;

interface Step1Props {
    step: number;
    totalSteps: number;
    completedSteps: number[];
    businessType: string | null;
}

export default function Step1BusinessType({
    step,
    totalSteps,
    completedSteps,
    businessType,
}: Step1Props) {
    const [selected, setSelected] = useState(businessType ?? '');

    return (
        <OnboardingLayout
            step={step}
            totalSteps={totalSteps}
            completedSteps={completedSteps}
            title="What type of grooming business do you run?"
            description="This helps us tailor your setup experience."
        >
            <Head title="Business Type - Onboarding" />
            <Form
                action={store.url(step)}
                method="post"
                className="space-y-6"
            >
                {({ processing, errors }) => (
                    <>
                        <input
                            type="hidden"
                            name="business_type"
                            value={selected}
                        />
                        <div className="grid gap-4 sm:grid-cols-2">
                            {BUSINESS_TYPES.map((type) => {
                                const Icon = type.icon;
                                const isSelected = selected === type.value;

                                return (
                                    <button
                                        key={type.value}
                                        type="button"
                                        onClick={() =>
                                            setSelected(type.value)
                                        }
                                        className={`flex flex-col items-center gap-3 rounded-xl border-2 p-6 text-center transition-colors ${
                                            isSelected
                                                ? 'border-primary bg-primary/5'
                                                : 'border-border hover:border-primary/50'
                                        }`}
                                    >
                                        <Icon
                                            className={`size-8 ${isSelected ? 'text-primary' : 'text-muted-foreground'}`}
                                        />
                                        <div>
                                            <p className="font-medium">
                                                {type.label}
                                            </p>
                                            <p className="mt-1 text-sm text-muted-foreground">
                                                {type.description}
                                            </p>
                                        </div>
                                    </button>
                                );
                            })}
                        </div>
                        {errors.business_type && (
                            <p className="text-sm text-red-600 dark:text-red-400">
                                {errors.business_type}
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
