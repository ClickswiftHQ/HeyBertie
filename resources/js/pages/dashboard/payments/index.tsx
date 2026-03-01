import { Head, useForm, usePage } from '@inertiajs/react';
import {
    CheckCircle2,
    CreditCard,
    ExternalLink,
    Loader2,
} from 'lucide-react';
import { FormEvent, useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

type PaymentsPageProps = {
    connectStatus: 'not_started' | 'pending' | 'complete';
    depositsEnabled: boolean;
    depositType: 'fixed' | 'percentage';
    depositFixedAmount: number;
    depositPercentage: number;
};

export default function Payments({
    connectStatus,
    depositsEnabled,
    depositType,
    depositFixedAmount,
    depositPercentage,
}: PaymentsPageProps) {
    const { currentBusiness } = usePage().props;
    const handle = currentBusiness!.handle;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: `/${handle}/dashboard` },
        { title: 'Payments', href: `/${handle}/payments` },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Payments" />
            <div className="flex flex-1 flex-col gap-6 p-4">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">
                        Payments
                    </h1>
                    <p className="text-muted-foreground text-sm">
                        Accept deposits and get paid when customers book.
                    </p>
                </div>

                <ConnectStatusCard
                    connectStatus={connectStatus}
                    handle={handle}
                />

                {connectStatus === 'complete' && (
                    <>
                        <HowItWorksCard />
                        <DepositSettingsCard
                            handle={handle}
                            depositsEnabled={depositsEnabled}
                            depositType={depositType}
                            depositFixedAmount={depositFixedAmount}
                            depositPercentage={depositPercentage}
                        />
                    </>
                )}
            </div>
        </AppLayout>
    );
}

function RedirectModal({
    message = "You're being redirected to our payment partner Stripe.com to enter your banking details and answer a few compliance questions to help prevent fraud.",
}: {
    message?: string;
}) {
    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
            <div className="mx-4 w-full max-w-md rounded-lg bg-white p-6 shadow-xl">
                <div className="flex flex-col items-center text-center">
                    <Loader2 className="text-primary mb-4 size-8 animate-spin" />
                    <h3 className="text-lg font-semibold text-gray-900">
                        Redirecting you now...
                    </h3>
                    <p className="mt-2 text-sm text-gray-500">{message}</p>
                    <p className="mt-3 text-xs text-gray-400">
                        Please don't close this page.
                    </p>
                </div>
            </div>
        </div>
    );
}

function HowItWorksCard() {
    return (
        <Card>
            <CardHeader>
                <CardTitle>Fees & Payouts</CardTitle>
                <CardDescription>
                    How payments work on HeyBertie.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div className="text-muted-foreground space-y-4 text-sm">
                    <div className="space-y-2">
                        <p className="font-medium text-gray-900">
                            When a customer pays a deposit:
                        </p>
                        <ul className="list-inside list-disc space-y-1">
                            <li>
                                HeyBertie takes a 2.5% platform fee on the
                                deposit amount
                            </li>
                            <li>
                                Stripe charges a card processing fee (typically
                                1.4% + 20p for UK cards)
                            </li>
                            <li>
                                The remainder is paid out to your bank account
                            </li>
                        </ul>
                    </div>
                    <div className="space-y-2">
                        <p className="font-medium text-gray-900">Payouts</p>
                        <p>
                            Payouts are sent to your bank account automatically.
                            New accounts typically receive payouts within 7
                            days, after which payouts arrive in 2 business days.
                            You can view payout history and update your bank
                            details using the "Manage Payment Details" button
                            above.
                        </p>
                    </div>
                    <div className="space-y-2">
                        <p className="font-medium text-gray-900">Refunds</p>
                        <p>
                            If a booking is cancelled, the customer's deposit is
                            automatically refunded in full. Both the platform
                            fee and the transfer to your account are reversed —
                            no action needed from you.
                        </p>
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}

function ConnectStatusCard({
    connectStatus,
    handle,
}: {
    connectStatus: 'not_started' | 'pending' | 'complete';
    handle: string;
}) {
    const connectForm = useForm({});
    const [showRedirectModal, setShowRedirectModal] = useState(false);

    function handleConnect(e: FormEvent) {
        e.preventDefault();
        setShowRedirectModal(true);
        connectForm.post(`/${handle}/payments/connect`);
    }

    const dashboardForm = useForm({});

    function handleDashboard(e: FormEvent) {
        e.preventDefault();
        setShowRedirectModal(true);
        dashboardForm.post(`/${handle}/payments/dashboard`);
    }

    if (connectStatus === 'complete') {
        return (
            <>
                {showRedirectModal && (
                    <RedirectModal message="You're being redirected to your payment dashboard where you can view payouts, update your bank details, and more." />
                )}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <CheckCircle2 className="size-5 text-green-600" />
                            Payments Active
                        </CardTitle>
                        <CardDescription>
                            Your account is set up and ready to accept payments.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleDashboard}>
                            <Button
                                type="submit"
                                variant="outline"
                                disabled={dashboardForm.processing}
                            >
                                {dashboardForm.processing ? (
                                    <Loader2 className="mr-2 size-4 animate-spin" />
                                ) : (
                                    <ExternalLink className="mr-2 size-4" />
                                )}
                                Manage Payment Details
                            </Button>
                        </form>
                    </CardContent>
                </Card>
            </>
        );
    }

    if (connectStatus === 'pending') {
        return (
            <>
                {showRedirectModal && <RedirectModal />}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <CreditCard className="size-5 text-amber-600" />
                            Setup in Progress
                        </CardTitle>
                        <CardDescription>
                            Your payment setup isn't complete yet. Continue where
                            you left off to start accepting payments.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleConnect}>
                            <Button
                                type="submit"
                                disabled={connectForm.processing}
                            >
                                {connectForm.processing ? (
                                    <Loader2 className="mr-2 size-4 animate-spin" />
                                ) : null}
                                Continue Setup
                            </Button>
                        </form>
                    </CardContent>
                </Card>
            </>
        );
    }

    return (
        <>
            {showRedirectModal && <RedirectModal />}
            <Card>
                <CardHeader>
                    <CardTitle>Set Up Payments</CardTitle>
                    <CardDescription>
                        Collect deposits when customers book online. HeyBertie
                        takes a small 2.5% platform fee on each deposit — the
                        rest goes directly to you.
                    </CardDescription>
                </CardHeader>
                <CardContent className="space-y-4">
                    <div className="text-muted-foreground space-y-2 text-sm">
                        <p>With payments enabled, you can:</p>
                        <ul className="list-inside list-disc space-y-1">
                            <li>
                                Require a deposit when customers book online
                            </li>
                            <li>
                                Choose a fixed amount or percentage of the
                                booking total
                            </li>
                            <li>
                                Deposits are automatically refunded if a booking
                                is cancelled
                            </li>
                        </ul>
                    </div>
                    <p className="text-muted-foreground text-xs">
                        You'll be redirected to our payment partner Stripe.com
                        to enter your banking details and answer a few
                        compliance questions.
                    </p>
                    <form onSubmit={handleConnect}>
                        <Button
                            type="submit"
                            disabled={connectForm.processing}
                        >
                            {connectForm.processing ? (
                                <Loader2 className="mr-2 size-4 animate-spin" />
                            ) : null}
                            Set Up Payments
                        </Button>
                    </form>
                </CardContent>
            </Card>
        </>
    );
}

function DepositSettingsCard({
    handle,
    depositsEnabled,
    depositType,
    depositFixedAmount,
    depositPercentage,
}: {
    handle: string;
    depositsEnabled: boolean;
    depositType: 'fixed' | 'percentage';
    depositFixedAmount: number;
    depositPercentage: number;
}) {
    const form = useForm({
        deposits_enabled: depositsEnabled,
        deposit_type: depositType,
        deposit_fixed_amount: depositFixedAmount,
        deposit_percentage: depositPercentage,
    });

    function submit(e: FormEvent) {
        e.preventDefault();
        form.post(`/${handle}/payments/settings`);
    }

    return (
        <Card>
            <CardHeader>
                <CardTitle>Deposit Settings</CardTitle>
                <CardDescription>
                    Configure whether customers pay a deposit when booking.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <form onSubmit={submit} className="space-y-6">
                    {/* Toggle */}
                    <div className="flex items-center gap-3">
                        <button
                            type="button"
                            role="switch"
                            aria-checked={form.data.deposits_enabled}
                            onClick={() =>
                                form.setData(
                                    'deposits_enabled',
                                    !form.data.deposits_enabled,
                                )
                            }
                            className={`relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors ${
                                form.data.deposits_enabled
                                    ? 'bg-primary'
                                    : 'bg-input'
                            }`}
                        >
                            <span
                                className={`pointer-events-none block size-5 rounded-full bg-white shadow-lg ring-0 transition-transform ${
                                    form.data.deposits_enabled
                                        ? 'translate-x-5'
                                        : 'translate-x-0'
                                }`}
                            />
                        </button>
                        <Label>Require deposit when booking</Label>
                    </div>

                    {form.data.deposits_enabled && (
                        <>
                            {/* Deposit Type */}
                            <div className="space-y-3">
                                <Label>Deposit type</Label>
                                <div className="flex gap-3">
                                    <button
                                        type="button"
                                        onClick={() =>
                                            form.setData(
                                                'deposit_type',
                                                'fixed',
                                            )
                                        }
                                        className={`rounded-md border px-4 py-2 text-sm font-medium transition-colors ${
                                            form.data.deposit_type === 'fixed'
                                                ? 'border-primary bg-primary/5 text-primary'
                                                : 'text-muted-foreground hover:bg-accent'
                                        }`}
                                    >
                                        Fixed amount
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() =>
                                            form.setData(
                                                'deposit_type',
                                                'percentage',
                                            )
                                        }
                                        className={`rounded-md border px-4 py-2 text-sm font-medium transition-colors ${
                                            form.data.deposit_type ===
                                            'percentage'
                                                ? 'border-primary bg-primary/5 text-primary'
                                                : 'text-muted-foreground hover:bg-accent'
                                        }`}
                                    >
                                        Percentage
                                    </button>
                                </div>
                            </div>

                            {/* Amount Input */}
                            {form.data.deposit_type === 'fixed' ? (
                                <div className="space-y-2">
                                    <Label htmlFor="deposit_fixed_amount">
                                        Deposit amount
                                    </Label>
                                    <div className="relative max-w-xs">
                                        <span className="text-muted-foreground absolute left-3 top-1/2 -translate-y-1/2 text-sm">
                                            &pound;
                                        </span>
                                        <Input
                                            id="deposit_fixed_amount"
                                            type="number"
                                            min="0.50"
                                            max="9999.99"
                                            step="0.01"
                                            className="pl-7"
                                            value={
                                                form.data.deposit_fixed_amount
                                            }
                                            onChange={(e) =>
                                                form.setData(
                                                    'deposit_fixed_amount',
                                                    parseFloat(
                                                        e.target.value,
                                                    ) || 0,
                                                )
                                            }
                                        />
                                    </div>
                                    {form.errors.deposit_fixed_amount && (
                                        <p className="text-sm text-red-600">
                                            {form.errors.deposit_fixed_amount}
                                        </p>
                                    )}
                                </div>
                            ) : (
                                <div className="space-y-2">
                                    <Label htmlFor="deposit_percentage">
                                        Deposit percentage
                                    </Label>
                                    <div className="relative max-w-xs">
                                        <Input
                                            id="deposit_percentage"
                                            type="number"
                                            min="1"
                                            max="100"
                                            step="1"
                                            value={form.data.deposit_percentage}
                                            onChange={(e) =>
                                                form.setData(
                                                    'deposit_percentage',
                                                    parseInt(
                                                        e.target.value,
                                                    ) || 0,
                                                )
                                            }
                                        />
                                        <span className="text-muted-foreground absolute right-3 top-1/2 -translate-y-1/2 text-sm">
                                            %
                                        </span>
                                    </div>
                                    {form.errors.deposit_percentage && (
                                        <p className="text-sm text-red-600">
                                            {form.errors.deposit_percentage}
                                        </p>
                                    )}
                                </div>
                            )}
                        </>
                    )}

                    <Button
                        type="submit"
                        disabled={form.processing}
                    >
                        {form.processing ? (
                            <Loader2 className="mr-2 size-4 animate-spin" />
                        ) : null}
                        Save Settings
                    </Button>
                </form>
            </CardContent>
        </Card>
    );
}
