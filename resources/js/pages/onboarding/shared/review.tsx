import { Head, Link, router } from '@inertiajs/react';
import {
    CheckCircleIcon,
    EditIcon,
    MapPinIcon,
    ShieldCheckIcon,
    TagIcon,
    UserIcon,
} from 'lucide-react';
import { type FormEvent, useState } from 'react';
import AppLogoIcon from '@/components/app-logo-icon';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Spinner } from '@/components/ui/spinner';
import { step as stepRoute, submit } from '@/routes/onboarding';

interface ReviewProps {
    business: {
        type: string;
        name: string;
        description: string | null;
        handle: string;
        phone: string | null;
        email: string | null;
        website: string | null;
        logo_url: string | null;
    };
    location: {
        name: string;
        location_type: string;
        address: string;
        service_radius_km: number | null;
    };
    services: Array<{
        name: string;
        duration_minutes: number;
        price: number | null;
        price_type: string;
    }>;
    verification: {
        documents_count: number;
        has_photo_id: boolean;
    };
    plan: {
        tier: string;
        name: string;
        price: number;
    };
}

const TYPE_LABELS: Record<string, string> = {
    salon: 'Salon',
    mobile: 'Mobile',
    home_based: 'Home-based',
    hybrid: 'Hybrid',
};

export default function Review({
    business,
    location,
    services,
    verification,
    plan,
}: ReviewProps) {
    const [agreed, setAgreed] = useState(false);
    const [processing, setProcessing] = useState(false);

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();
        setProcessing(true);
        router.post(submit.url(), {}, {
            onFinish: () => setProcessing(false),
        });
    };

    const formatPrice = (price: number | null, priceType: string) => {
        if (priceType === 'call') return 'Price on request';
        if (price === null) return '-';
        const formatted = `\u00A3${Number(price).toFixed(2)}`;
        return priceType === 'from' ? `From ${formatted}` : formatted;
    };

    return (
        <div className="flex min-h-svh flex-col bg-background">
            <header className="border-b">
                <div className="mx-auto flex max-w-3xl items-center justify-between px-6 py-4">
                    <AppLogoIcon className="size-7 fill-current text-foreground" />
                    <span className="text-sm font-medium text-green-600">
                        <CheckCircleIcon className="mr-1 inline size-4" />
                        All steps complete
                    </span>
                </div>
            </header>

            <div className="mx-auto w-full max-w-3xl px-6 py-8">
                <div className="space-y-2">
                    <h1 className="text-2xl font-semibold">
                        Review your business
                    </h1>
                    <p className="text-muted-foreground">
                        Check everything looks good before creating your
                        business.
                    </p>
                </div>

                <div className="mt-8 space-y-4">
                    <Card>
                        <CardHeader className="flex-row items-center justify-between">
                            <CardTitle className="flex items-center gap-2">
                                <UserIcon className="size-4" />
                                Business Details
                            </CardTitle>
                            <Link
                                href={stepRoute.url(1)}
                                className="text-sm text-primary hover:underline"
                            >
                                <EditIcon className="mr-1 inline size-3" />
                                Edit
                            </Link>
                        </CardHeader>
                        <CardContent>
                            <dl className="grid gap-2 text-sm sm:grid-cols-2">
                                <div>
                                    <dt className="text-muted-foreground">
                                        Type
                                    </dt>
                                    <dd className="font-medium">
                                        {TYPE_LABELS[business.type] ??
                                            business.type}
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-muted-foreground">
                                        Name
                                    </dt>
                                    <dd className="font-medium">
                                        {business.name}
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-muted-foreground">
                                        Handle
                                    </dt>
                                    <dd className="font-medium">
                                        @{business.handle}
                                    </dd>
                                </div>
                                {business.phone && (
                                    <div>
                                        <dt className="text-muted-foreground">
                                            Phone
                                        </dt>
                                        <dd>{business.phone}</dd>
                                    </div>
                                )}
                                {business.email && (
                                    <div>
                                        <dt className="text-muted-foreground">
                                            Email
                                        </dt>
                                        <dd>{business.email}</dd>
                                    </div>
                                )}
                            </dl>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex-row items-center justify-between">
                            <CardTitle className="flex items-center gap-2">
                                <MapPinIcon className="size-4" />
                                Location
                            </CardTitle>
                            <Link
                                href={stepRoute.url(4)}
                                className="text-sm text-primary hover:underline"
                            >
                                <EditIcon className="mr-1 inline size-3" />
                                Edit
                            </Link>
                        </CardHeader>
                        <CardContent>
                            <dl className="grid gap-2 text-sm sm:grid-cols-2">
                                <div>
                                    <dt className="text-muted-foreground">
                                        Address
                                    </dt>
                                    <dd>{location.address}</dd>
                                </div>
                                <div>
                                    <dt className="text-muted-foreground">
                                        Type
                                    </dt>
                                    <dd className="capitalize">
                                        {location.location_type.replace(
                                            '_',
                                            ' ',
                                        )}
                                    </dd>
                                </div>
                                {location.service_radius_km && (
                                    <div>
                                        <dt className="text-muted-foreground">
                                            Service Radius
                                        </dt>
                                        <dd>
                                            {location.service_radius_km} km
                                        </dd>
                                    </div>
                                )}
                            </dl>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex-row items-center justify-between">
                            <CardTitle className="flex items-center gap-2">
                                <TagIcon className="size-4" />
                                Services ({services.length})
                            </CardTitle>
                            <Link
                                href={stepRoute.url(5)}
                                className="text-sm text-primary hover:underline"
                            >
                                <EditIcon className="mr-1 inline size-3" />
                                Edit
                            </Link>
                        </CardHeader>
                        <CardContent>
                            <div className="divide-y">
                                {services.map((service, i) => (
                                    <div
                                        key={i}
                                        className="flex items-center justify-between py-2 text-sm"
                                    >
                                        <div>
                                            <span className="font-medium">
                                                {service.name}
                                            </span>
                                            <span className="ml-2 text-muted-foreground">
                                                {service.duration_minutes} min
                                            </span>
                                        </div>
                                        <span>
                                            {formatPrice(
                                                service.price,
                                                service.price_type,
                                            )}
                                        </span>
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex-row items-center justify-between">
                            <CardTitle className="flex items-center gap-2">
                                <ShieldCheckIcon className="size-4" />
                                Verification
                            </CardTitle>
                            <Link
                                href={stepRoute.url(6)}
                                className="text-sm text-primary hover:underline"
                            >
                                <EditIcon className="mr-1 inline size-3" />
                                Edit
                            </Link>
                        </CardHeader>
                        <CardContent>
                            <p className="text-sm">
                                {verification.documents_count} document(s)
                                uploaded
                                {verification.has_photo_id && (
                                    <span className="ml-2 text-green-600">
                                        <CheckCircleIcon className="mr-1 inline size-3" />
                                        Photo ID provided
                                    </span>
                                )}
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Selected Plan</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="flex items-center justify-between text-sm">
                                <span className="font-medium">
                                    {plan.name}
                                </span>
                                <span>
                                    {plan.price > 0
                                        ? `\u00A3${plan.price}/month`
                                        : 'Free'}
                                </span>
                            </div>
                            {plan.price > 0 && (
                                <p className="mt-1 text-xs text-muted-foreground">
                                    Includes 14-day free trial
                                </p>
                            )}
                        </CardContent>
                    </Card>
                </div>

                <form onSubmit={handleSubmit} className="mt-8 space-y-6">
                    <div className="flex items-start gap-3">
                        <Checkbox
                            id="terms"
                            checked={agreed}
                            onCheckedChange={(checked) =>
                                setAgreed(checked === true)
                            }
                        />
                        <label
                            htmlFor="terms"
                            className="text-sm leading-relaxed"
                        >
                            I agree to the{' '}
                            <a
                                href="/terms"
                                className="text-primary underline"
                                target="_blank"
                            >
                                Terms of Service
                            </a>{' '}
                            and{' '}
                            <a
                                href="/privacy"
                                className="text-primary underline"
                                target="_blank"
                            >
                                Privacy Policy
                            </a>
                        </label>
                    </div>

                    <Button
                        type="submit"
                        size="lg"
                        className="w-full"
                        disabled={!agreed || processing}
                    >
                        {processing ? (
                            <Spinner />
                        ) : (
                            'Create My Business'
                        )}
                    </Button>
                </form>
            </div>
        </div>
    );
}
