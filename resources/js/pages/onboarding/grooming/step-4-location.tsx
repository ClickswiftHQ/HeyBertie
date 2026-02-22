import { Form, Head } from '@inertiajs/react';
import { useRef, useState } from 'react';
import InputError from '@/components/input-error';
import StepNavigation from '@/components/onboarding/step-navigation';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import OnboardingLayout from '@/layouts/onboarding-layout';
import { store } from '@/routes/onboarding';
import { lookup } from '@/routes/postcode';

interface Address {
    line_1: string;
    line_2: string;
    line_3: string;
    post_town: string;
    county: string;
    postcode: string;
    latitude: number;
    longitude: number;
}

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
    const [addresses, setAddresses] = useState<Address[]>([]);
    const [lookupLoading, setLookupLoading] = useState(false);
    const [lookupError, setLookupError] = useState('');

    const addressLine1Ref = useRef<HTMLInputElement>(null);
    const addressLine2Ref = useRef<HTMLInputElement>(null);
    const townRef = useRef<HTMLInputElement>(null);
    const cityRef = useRef<HTMLInputElement>(null);
    const postcodeRef = useRef<HTMLInputElement>(null);
    const countyRef = useRef<HTMLInputElement>(null);

    async function handleFindAddress() {
        const postcode = postcodeRef.current?.value?.trim();
        if (!postcode) {
            setLookupError('Please enter a postcode first.');
            return;
        }

        setLookupLoading(true);
        setLookupError('');
        setAddresses([]);

        try {
            const response = await fetch(lookup.url(postcode));
            const data: Address[] = await response.json();

            if (data.length === 0) {
                setLookupError(
                    'No addresses found for this postcode. Please enter your address manually.',
                );
            } else {
                setAddresses(data);
            }
        } catch {
            setLookupError(
                'Could not look up addresses. Please enter your address manually.',
            );
        } finally {
            setLookupLoading(false);
        }
    }

    function handleSelectAddress(index: number) {
        const address = addresses[index];
        if (!address) return;

        const setNativeValue = (ref: React.RefObject<HTMLInputElement | null>, value: string) => {
            const input = ref.current;
            if (!input) return;
            const nativeInputValueSetter = Object.getOwnPropertyDescriptor(
                window.HTMLInputElement.prototype,
                'value',
            )?.set;
            nativeInputValueSetter?.call(input, value);
            input.dispatchEvent(new Event('input', { bubbles: true }));
        };

        setNativeValue(addressLine1Ref, address.line_1);
        setNativeValue(
            addressLine2Ref,
            [address.line_2, address.line_3].filter(Boolean).join(', '),
        );
        setNativeValue(townRef, address.post_town);
        setNativeValue(cityRef, address.post_town);
        setNativeValue(countyRef, address.county);

        setAddresses([]);
    }

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
                                <Label htmlFor="postcode">
                                    Postcode{' '}
                                    <span className="text-red-500">*</span>
                                </Label>
                                <div className="flex gap-2">
                                    <Input
                                        ref={postcodeRef}
                                        id="postcode"
                                        name="postcode"
                                        type="text"
                                        required
                                        autoFocus
                                        defaultValue={location.postcode ?? ''}
                                        placeholder="SW1A 1AA"
                                        className="flex-1"
                                    />
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={handleFindAddress}
                                        disabled={lookupLoading}
                                    >
                                        {lookupLoading
                                            ? 'Looking up...'
                                            : 'Find Address'}
                                    </Button>
                                </div>
                                <InputError message={errors.postcode} />
                                {lookupError && (
                                    <p className="text-sm text-muted-foreground">
                                        {lookupError}
                                    </p>
                                )}
                            </div>

                            {addresses.length > 0 && (
                                <div className="grid gap-2">
                                    <Label htmlFor="address-select">
                                        Select an address
                                    </Label>
                                    <select
                                        id="address-select"
                                        onChange={(e) =>
                                            handleSelectAddress(
                                                Number(e.target.value),
                                            )
                                        }
                                        defaultValue=""
                                        className="border-input flex h-9 w-full rounded-md border bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]"
                                    >
                                        <option value="" disabled>
                                            {addresses.length} address
                                            {addresses.length !== 1
                                                ? 'es'
                                                : ''}{' '}
                                            found — select one
                                        </option>
                                        {addresses.map((addr, i) => (
                                            <option key={i} value={i}>
                                                {[
                                                    addr.line_1,
                                                    addr.line_2,
                                                    addr.post_town,
                                                ]
                                                    .filter(Boolean)
                                                    .join(', ')}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                            )}

                            <div className="grid gap-2">
                                <Label htmlFor="address_line_1">
                                    Address Line 1{' '}
                                    <span className="text-red-500">*</span>
                                </Label>
                                <Input
                                    ref={addressLine1Ref}
                                    id="address_line_1"
                                    name="address_line_1"
                                    type="text"
                                    required
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
                                    ref={addressLine2Ref}
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
                                        ref={townRef}
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
                                        ref={cityRef}
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
                                    <Label htmlFor="county">County</Label>
                                    <Input
                                        ref={countyRef}
                                        id="county"
                                        name="county"
                                        type="text"
                                        defaultValue={location.county ?? ''}
                                    />
                                    <InputError message={errors.county} />
                                </div>

                                <div className="hidden">
                                    {/* Spacer for grid alignment */}
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
