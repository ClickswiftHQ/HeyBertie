import { Head, router } from '@inertiajs/react';
import { PlusIcon, TrashIcon } from 'lucide-react';
import { type FormEvent, useState } from 'react';
import InputError from '@/components/input-error';
import StepNavigation from '@/components/onboarding/step-navigation';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import OnboardingLayout from '@/layouts/onboarding-layout';
import { store } from '@/routes/onboarding';

interface ServiceItem {
    name: string;
    description: string;
    duration_minutes: number;
    price: number | null;
    price_type: 'fixed' | 'from' | 'call';
}

interface SuggestedService {
    name: string;
    typical_duration: number;
    typical_price: number;
}

interface Step5Props {
    step: number;
    totalSteps: number;
    completedSteps: number[];
    services: ServiceItem[];
    suggestedServices: SuggestedService[];
}

export default function Step5Services({
    step,
    totalSteps,
    completedSteps,
    services: initialServices,
    suggestedServices,
}: Step5Props) {
    const [services, setServices] = useState<ServiceItem[]>(
        initialServices.length > 0
            ? initialServices
            : [],
    );
    const [processing, setProcessing] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});

    const addService = (suggested?: SuggestedService) => {
        setServices([
            ...services,
            {
                name: suggested?.name ?? '',
                description: '',
                duration_minutes: suggested?.typical_duration ?? 60,
                price: suggested?.typical_price ?? null,
                price_type: 'fixed',
            },
        ]);
    };

    const removeService = (index: number) => {
        setServices(services.filter((_, i) => i !== index));
    };

    const updateService = (
        index: number,
        field: keyof ServiceItem,
        value: string | number | null,
    ) => {
        setServices(
            services.map((s, i) =>
                i === index ? { ...s, [field]: value } : s,
            ),
        );
    };

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();
        setProcessing(true);
        setErrors({});

        router.post(
            store.url(step),
            { services },
            {
                onError: (errs) => {
                    setErrors(errs);
                    setProcessing(false);
                },
                onFinish: () => setProcessing(false),
            },
        );
    };

    const unusedSuggestions = suggestedServices.filter(
        (s) => !services.some((svc) => svc.name === s.name),
    );

    return (
        <OnboardingLayout
            step={step}
            totalSteps={totalSteps}
            completedSteps={completedSteps}
            title="What services do you offer?"
            description="Add at least one service. You can update these later."
        >
            <Head title="Services - Onboarding" />
            <form onSubmit={handleSubmit} className="space-y-6">
                {unusedSuggestions.length > 0 && (
                    <div>
                        <p className="mb-2 text-sm font-medium">
                            Quick add suggested services:
                        </p>
                        <div className="flex flex-wrap gap-2">
                            {unusedSuggestions.map((suggestion) => (
                                <button
                                    key={suggestion.name}
                                    type="button"
                                    onClick={() => addService(suggestion)}
                                    className="rounded-full border px-3 py-1 text-sm hover:border-primary hover:text-primary"
                                >
                                    + {suggestion.name}
                                </button>
                            ))}
                        </div>
                    </div>
                )}

                <div className="space-y-4">
                    {services.map((service, index) => (
                        <div
                            key={index}
                            className="rounded-lg border p-4"
                        >
                            <div className="mb-4 flex items-start justify-between">
                                <span className="text-sm font-medium text-muted-foreground">
                                    Service {index + 1}
                                </span>
                                {services.length > 1 && (
                                    <button
                                        type="button"
                                        onClick={() => removeService(index)}
                                        className="text-muted-foreground hover:text-red-500"
                                    >
                                        <TrashIcon className="size-4" />
                                    </button>
                                )}
                            </div>

                            <div className="grid gap-4">
                                <div className="grid gap-2">
                                    <Label>Name</Label>
                                    <Input
                                        value={service.name}
                                        onChange={(e) =>
                                            updateService(
                                                index,
                                                'name',
                                                e.target.value,
                                            )
                                        }
                                        placeholder="e.g. Full Groom"
                                        required
                                    />
                                    <InputError
                                        message={
                                            errors[`services.${index}.name`]
                                        }
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label>Description</Label>
                                    <Input
                                        value={service.description}
                                        onChange={(e) =>
                                            updateService(
                                                index,
                                                'description',
                                                e.target.value,
                                            )
                                        }
                                        placeholder="Brief description of the service"
                                    />
                                    <InputError
                                        message={
                                            errors[`services.${index}.description`]
                                        }
                                    />
                                </div>

                                <div className="grid gap-4 sm:grid-cols-3">
                                    <div className="grid gap-2">
                                        <Label>Duration (min)</Label>
                                        <Input
                                            type="number"
                                            min={5}
                                            max={480}
                                            value={service.duration_minutes}
                                            onChange={(e) =>
                                                updateService(
                                                    index,
                                                    'duration_minutes',
                                                    parseInt(e.target.value) ||
                                                        0,
                                                )
                                            }
                                            required
                                        />
                                        <InputError
                                            message={
                                                errors[
                                                    `services.${index}.duration_minutes`
                                                ]
                                            }
                                        />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label>Price Type</Label>
                                        <select
                                            value={service.price_type}
                                            onChange={(e) =>
                                                updateService(
                                                    index,
                                                    'price_type',
                                                    e.target.value,
                                                )
                                            }
                                            className="border-input flex h-9 w-full rounded-md border bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]"
                                        >
                                            <option value="fixed">
                                                Fixed Price
                                            </option>
                                            <option value="from">From</option>
                                            <option value="call">
                                                Price on Request
                                            </option>
                                        </select>
                                    </div>

                                    {service.price_type !== 'call' && (
                                        <div className="grid gap-2">
                                            <Label>Price (&pound;)</Label>
                                            <Input
                                                type="number"
                                                min={0}
                                                step={0.01}
                                                value={service.price ?? ''}
                                                onChange={(e) =>
                                                    updateService(
                                                        index,
                                                        'price',
                                                        e.target.value
                                                            ? parseFloat(
                                                                  e.target
                                                                      .value,
                                                              )
                                                            : null,
                                                    )
                                                }
                                                placeholder="0.00"
                                            />
                                            <InputError
                                                message={
                                                    errors[
                                                        `services.${index}.price`
                                                    ]
                                                }
                                            />
                                        </div>
                                    )}
                                </div>
                            </div>
                        </div>
                    ))}
                </div>

                {errors.services && (
                    <p className="text-sm text-red-600 dark:text-red-400">
                        {errors.services}
                    </p>
                )}

                <Button
                    type="button"
                    variant="outline"
                    onClick={() => addService()}
                    className="w-full"
                >
                    <PlusIcon className="size-4" />
                    Add Custom Service
                </Button>

                <StepNavigation
                    currentStep={step}
                    totalSteps={totalSteps}
                    processing={processing}
                    nextDisabled={services.length === 0}
                />
            </form>
        </OnboardingLayout>
    );
}
