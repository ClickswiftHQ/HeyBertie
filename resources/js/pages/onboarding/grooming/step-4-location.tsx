import { Form, Head } from '@inertiajs/react';
import InputError from '@/components/input-error';
import StepNavigation from '@/components/onboarding/step-navigation';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import OnboardingLayout from '@/layouts/onboarding-layout';
import { store } from '@/routes/onboarding';

interface Step4Props {
    step: number;
    totalSteps: number;
    completedSteps: number[];
    businessType: string;
    location: {
        name: string | null;
        location_type: string | null;
        address_line_1: string | null;
        address_line_2: string | null;
        town: string | null;
        city: string | null;
        postcode: string | null;
        county: string | null;
        service_radius_km: number | null;
        phone: string | null;
        email: string | null;
    };
}

export default function Step4Location({
    step,
    totalSteps,
    completedSteps,
    businessType,
    location,
}: Step4Props) {
    const showRadius = businessType === 'mobile' || businessType === 'hybrid';

    return (
        <OnboardingLayout
            step={step}
            totalSteps={totalSteps}
            completedSteps={completedSteps}
            title="Where are you based?"
            description={
                businessType === 'mobile'
                    ? 'Enter your base address and service area.'
                    : 'Enter your business location details.'
            }
        >
            <Head title="Location - Onboarding" />
            <Form
                action={store.url(step)}
                method="post"
                className="space-y-6"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-6">
                            <div className="grid gap-2">
                                <Label htmlFor="name">Location Name</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    type="text"
                                    defaultValue={location.name ?? ''}
                                    placeholder="e.g. Main Salon"
                                />
                                <InputError message={errors.name} />
                            </div>

                            {businessType === 'hybrid' && (
                                <div className="grid gap-2">
                                    <Label htmlFor="location_type">
                                        Location Type
                                    </Label>
                                    <select
                                        id="location_type"
                                        name="location_type"
                                        defaultValue={
                                            location.location_type ?? 'salon'
                                        }
                                        className="border-input flex h-9 w-full rounded-md border bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]"
                                    >
                                        <option value="salon">Salon</option>
                                        <option value="mobile">Mobile</option>
                                    </select>
                                    <InputError
                                        message={errors.location_type}
                                    />
                                </div>
                            )}

                            <div className="grid gap-2">
                                <Label htmlFor="address_line_1">
                                    Address Line 1{' '}
                                    <span className="text-red-500">*</span>
                                </Label>
                                <Input
                                    id="address_line_1"
                                    name="address_line_1"
                                    type="text"
                                    required
                                    autoFocus
                                    defaultValue={
                                        location.address_line_1 ?? ''
                                    }
                                    placeholder="Street address"
                                />
                                <InputError
                                    message={errors.address_line_1}
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="address_line_2">
                                    Address Line 2
                                </Label>
                                <Input
                                    id="address_line_2"
                                    name="address_line_2"
                                    type="text"
                                    defaultValue={
                                        location.address_line_2 ?? ''
                                    }
                                    placeholder="Flat, suite, etc."
                                />
                                <InputError
                                    message={errors.address_line_2}
                                />
                            </div>

                            <div className="grid gap-6 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="town">
                                        Town / Area{' '}
                                        <span className="text-red-500">*</span>
                                    </Label>
                                    <Input
                                        id="town"
                                        name="town"
                                        type="text"
                                        required
                                        defaultValue={location.town ?? ''}
                                        placeholder="e.g. Fulham"
                                    />
                                    <InputError message={errors.town} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="city">
                                        City{' '}
                                        <span className="text-red-500">*</span>
                                    </Label>
                                    <Input
                                        id="city"
                                        name="city"
                                        type="text"
                                        required
                                        defaultValue={location.city ?? ''}
                                        placeholder="e.g. London"
                                    />
                                    <InputError message={errors.city} />
                                </div>
                            </div>

                            <div className="grid gap-6 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="postcode">
                                        Postcode{' '}
                                        <span className="text-red-500">*</span>
                                    </Label>
                                    <Input
                                        id="postcode"
                                        name="postcode"
                                        type="text"
                                        required
                                        defaultValue={location.postcode ?? ''}
                                        placeholder="SW1A 1AA"
                                    />
                                    <InputError message={errors.postcode} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="county">County</Label>
                                    <Input
                                        id="county"
                                        name="county"
                                        type="text"
                                        defaultValue={location.county ?? ''}
                                    />
                                    <InputError message={errors.county} />
                                </div>
                            </div>

                            {showRadius && (
                                <div className="grid gap-2">
                                    <Label htmlFor="service_radius_km">
                                        Service Radius (km)
                                        {businessType === 'mobile' && (
                                            <span className="text-red-500">
                                                {' '}
                                                *
                                            </span>
                                        )}
                                    </Label>
                                    <Input
                                        id="service_radius_km"
                                        name="service_radius_km"
                                        type="number"
                                        min={1}
                                        max={100}
                                        required={businessType === 'mobile'}
                                        defaultValue={
                                            location.service_radius_km ?? ''
                                        }
                                        placeholder="e.g. 15"
                                    />
                                    <p className="text-xs text-muted-foreground">
                                        How far you travel from your base
                                        address.
                                    </p>
                                    <InputError
                                        message={errors.service_radius_km}
                                    />
                                </div>
                            )}

                            <div className="grid gap-6 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="phone">
                                        Location Phone
                                    </Label>
                                    <Input
                                        id="phone"
                                        name="phone"
                                        type="tel"
                                        defaultValue={location.phone ?? ''}
                                    />
                                    <InputError message={errors.phone} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="email">
                                        Location Email
                                    </Label>
                                    <Input
                                        id="email"
                                        name="email"
                                        type="email"
                                        defaultValue={location.email ?? ''}
                                    />
                                    <InputError message={errors.email} />
                                </div>
                            </div>
                        </div>

                        <StepNavigation
                            currentStep={step}
                            totalSteps={totalSteps}
                            processing={processing}
                        />
                    </>
                )}
            </Form>
        </OnboardingLayout>
    );
}
