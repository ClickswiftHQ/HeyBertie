import { Form, Head } from '@inertiajs/react';
import InputError from '@/components/input-error';
import StepNavigation from '@/components/onboarding/step-navigation';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import OnboardingLayout from '@/layouts/onboarding-layout';
import { store } from '@/routes/onboarding';

interface Step2Props {
    step: number;
    totalSteps: number;
    completedSteps: number[];
    business: {
        name: string | null;
        description: string | null;
        phone: string | null;
        email: string | null;
        website: string | null;
        logo_url: string | null;
    };
}

export default function Step2BusinessDetails({
    step,
    totalSteps,
    completedSteps,
    business,
}: Step2Props) {
    return (
        <OnboardingLayout
            step={step}
            totalSteps={totalSteps}
            completedSteps={completedSteps}
            title="Tell us about your business"
            description="This information will appear on your public listing."
        >
            <Head title="Business Details - Onboarding" />
            <Form
                action={store.url(step)}
                method="post"
                encType="multipart/form-data"
                className="space-y-6"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-6">
                            <div className="grid gap-2">
                                <Label htmlFor="name">
                                    Business Name{' '}
                                    <span className="text-red-500">*</span>
                                </Label>
                                <Input
                                    id="name"
                                    name="name"
                                    type="text"
                                    required
                                    autoFocus
                                    defaultValue={business.name ?? ''}
                                    placeholder="e.g. Muddy Paws Grooming"
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="description">
                                    Description
                                </Label>
                                <textarea
                                    id="description"
                                    name="description"
                                    rows={3}
                                    maxLength={1000}
                                    defaultValue={business.description ?? ''}
                                    placeholder="Tell customers what makes your business special..."
                                    className="border-input placeholder:text-muted-foreground flex w-full rounded-md border bg-transparent px-3 py-2 text-sm shadow-xs focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] outline-none"
                                />
                                <InputError message={errors.description} />
                            </div>

                            <div className="grid gap-6 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="phone">Phone</Label>
                                    <Input
                                        id="phone"
                                        name="phone"
                                        type="tel"
                                        defaultValue={business.phone ?? ''}
                                        placeholder="07700 900000"
                                    />
                                    <InputError message={errors.phone} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="email">
                                        Business Email
                                    </Label>
                                    <Input
                                        id="email"
                                        name="email"
                                        type="email"
                                        defaultValue={business.email ?? ''}
                                        placeholder="hello@yourbusiness.co.uk"
                                    />
                                    <InputError message={errors.email} />
                                </div>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="website">Website</Label>
                                <Input
                                    id="website"
                                    name="website"
                                    type="url"
                                    defaultValue={business.website ?? ''}
                                    placeholder="https://yourbusiness.co.uk"
                                />
                                <InputError message={errors.website} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="logo">Logo</Label>
                                <Input
                                    id="logo"
                                    name="logo"
                                    type="file"
                                    accept="image/jpeg,image/png,image/webp"
                                />
                                <p className="text-xs text-muted-foreground">
                                    JPG, PNG, or WebP. Max 2MB.
                                </p>
                                <InputError message={errors.logo} />
                                {business.logo_url && (
                                    <img
                                        src={`/storage/${business.logo_url}`}
                                        alt="Current logo"
                                        className="size-16 rounded-md object-cover"
                                    />
                                )}
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
