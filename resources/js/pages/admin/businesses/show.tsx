import { Head, Link, router, useForm } from '@inertiajs/react';
import {
    Building2,
    Calendar,
    CreditCard,
    ExternalLink,
    Eye,
    MapPin,
    PoundSterling,
    Users,
} from 'lucide-react';
import { useState } from 'react';
import { ActivityTimeline } from '@/components/admin/activity-timeline';
import { StatCard } from '@/components/dashboard/stat-card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AdminLayout from '@/layouts/admin-layout';
import type {
    AdminBookingListItem,
    AdminBusinessDetail,
    AdminBusinessStats,
    AdminStatus,
    AdminTier,
    AdminTimelineEvent,
    BreadcrumbItem,
} from '@/types';

type Props = {
    business: AdminBusinessDetail;
    recentBookings: AdminBookingListItem[];
    stats: AdminBusinessStats;
    timeline: AdminTimelineEvent[];
    tiers: AdminTier[];
    statuses: AdminStatus[];
};

function VerificationBadge({ status }: { status: string }) {
    if (status === 'verified') {
        return (
            <Badge className="border-0 bg-emerald-100 text-emerald-700">
                Verified
            </Badge>
        );
    }
    if (status === 'rejected') {
        return <Badge variant="destructive">Rejected</Badge>;
    }
    return (
        <Badge className="border-0 bg-amber-100 text-amber-700">Pending</Badge>
    );
}

function BookingStatusBadge({ status }: { status: string }) {
    const styles: Record<string, string> = {
        pending: 'bg-amber-100 text-amber-700 border-0',
        confirmed: 'bg-blue-100 text-blue-700 border-0',
        completed: 'bg-emerald-100 text-emerald-700 border-0',
        cancelled: 'bg-red-100 text-red-700 border-0',
        no_show: 'bg-gray-100 text-gray-700 border-0',
    };
    return <Badge className={styles[status] ?? ''}>{status}</Badge>;
}

function VerifySection({
    business,
}: {
    business: AdminBusinessDetail;
}) {
    const form = useForm({ decision: '' as string, notes: '' });

    function submit(decision: 'approved' | 'rejected') {
        form.setData('decision', decision);
        form.post(`/admin/businesses/${business.id}/verify`, {
            preserveScroll: true,
        });
    }

    if (business.verification_status !== 'pending') {
        return null;
    }

    return (
        <Card className="border-amber-200 bg-amber-50 dark:border-amber-900 dark:bg-amber-950">
            <CardHeader>
                <CardTitle>Pending Verification</CardTitle>
            </CardHeader>
            <CardContent className="space-y-3">
                {business.verification_documents.length > 0 && (
                    <div className="space-y-2">
                        <p className="text-sm font-medium">Documents</p>
                        {business.verification_documents.map((doc) => (
                            <div
                                key={doc.id}
                                className="bg-background flex items-center justify-between rounded-md border p-2"
                            >
                                <div>
                                    <p className="text-sm font-medium capitalize">
                                        {doc.document_type.replace('_', ' ')}
                                    </p>
                                    <p className="text-muted-foreground text-xs">
                                        {doc.original_filename}
                                    </p>
                                </div>
                                <Badge variant="outline">{doc.status}</Badge>
                            </div>
                        ))}
                    </div>
                )}
                <div>
                    <Label htmlFor="verify-notes">Notes (optional)</Label>
                    <Textarea
                        id="verify-notes"
                        value={form.data.notes}
                        onChange={(e) => form.setData('notes', e.target.value)}
                        placeholder="Add review notes..."
                        rows={2}
                    />
                </div>
                <div className="flex gap-2">
                    <Button
                        onClick={() => submit('approved')}
                        disabled={form.processing}
                    >
                        Approve
                    </Button>
                    <Button
                        variant="destructive"
                        onClick={() => submit('rejected')}
                        disabled={form.processing}
                    >
                        Reject
                    </Button>
                </div>
            </CardContent>
        </Card>
    );
}

function SubscriptionSection({
    business,
    tiers,
    statuses,
}: {
    business: AdminBusinessDetail;
    tiers: AdminTier[];
    statuses: AdminStatus[];
}) {
    const subForm = useForm({
        subscription_tier_id: String(business.subscription_tier.id),
        subscription_status_id: String(business.subscription_status.id),
    });

    const trialForm = useForm({
        trial_ends_at: business.trial_ends_at
            ? business.trial_ends_at.slice(0, 10)
            : '',
    });

    return (
        <Card>
            <CardHeader>
                <CardTitle>Subscription</CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
                <div className="grid gap-3 sm:grid-cols-2">
                    <div>
                        <Label>Tier</Label>
                        <Select
                            value={subForm.data.subscription_tier_id}
                            onValueChange={(v) =>
                                subForm.setData('subscription_tier_id', v)
                            }
                        >
                            <SelectTrigger>
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {tiers.map((t) => (
                                    <SelectItem
                                        key={t.id}
                                        value={String(t.id)}
                                    >
                                        {t.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                    <div>
                        <Label>Status</Label>
                        <Select
                            value={subForm.data.subscription_status_id}
                            onValueChange={(v) =>
                                subForm.setData('subscription_status_id', v)
                            }
                        >
                            <SelectTrigger>
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {statuses.map((s) => (
                                    <SelectItem
                                        key={s.id}
                                        value={String(s.id)}
                                    >
                                        {s.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                </div>
                <Button
                    size="sm"
                    onClick={() =>
                        subForm.patch(
                            `/admin/businesses/${business.id}/subscription`,
                            { preserveScroll: true },
                        )
                    }
                    disabled={subForm.processing}
                >
                    Update Subscription
                </Button>

                <div className="border-t pt-4">
                    <Label>Trial End Date</Label>
                    <div className="mt-1 flex gap-2">
                        <Input
                            type="date"
                            value={trialForm.data.trial_ends_at}
                            onChange={(e) =>
                                trialForm.setData(
                                    'trial_ends_at',
                                    e.target.value,
                                )
                            }
                            className="w-48"
                        />
                        <Button
                            size="sm"
                            variant="secondary"
                            onClick={() =>
                                trialForm.patch(
                                    `/admin/businesses/${business.id}/trial`,
                                    { preserveScroll: true },
                                )
                            }
                            disabled={trialForm.processing}
                        >
                            Update Trial
                        </Button>
                    </div>
                </div>

                {business.stripe_id && (
                    <p className="text-muted-foreground text-xs">
                        Stripe Customer:{' '}
                        <span className="font-mono">{business.stripe_id}</span>
                    </p>
                )}
            </CardContent>
        </Card>
    );
}

function HandleSection({ business }: { business: AdminBusinessDetail }) {
    const form = useForm({ handle: business.handle });

    return (
        <Card>
            <CardHeader>
                <CardTitle>Handle</CardTitle>
            </CardHeader>
            <CardContent className="space-y-3">
                <div className="flex gap-2">
                    <div className="relative flex-1">
                        <span className="text-muted-foreground absolute top-2 left-3 text-sm">
                            @
                        </span>
                        <Input
                            value={form.data.handle}
                            onChange={(e) =>
                                form.setData('handle', e.target.value)
                            }
                            className="pl-7"
                        />
                    </div>
                    <Button
                        size="sm"
                        onClick={() =>
                            form.patch(
                                `/admin/businesses/${business.id}/handle`,
                                { preserveScroll: true },
                            )
                        }
                        disabled={
                            form.processing ||
                            form.data.handle === business.handle
                        }
                    >
                        Change
                    </Button>
                </div>
                {form.errors.handle && (
                    <p className="text-sm text-red-600">{form.errors.handle}</p>
                )}
                <p className="text-muted-foreground text-xs">
                    Admin handle changes bypass the 30-day cooldown. A redirect
                    from the old handle will be created automatically.
                </p>
            </CardContent>
        </Card>
    );
}

function SuspendSection({ business }: { business: AdminBusinessDetail }) {
    const [reason, setReason] = useState('');

    function handleAction(action: 'suspend' | 'reactivate') {
        router.post(
            `/admin/businesses/${business.id}/suspend`,
            { action, reason },
            { preserveScroll: true },
        );
    }

    return (
        <Card className="border-red-200 dark:border-red-900">
            <CardHeader>
                <CardTitle className="text-red-600">Danger Zone</CardTitle>
            </CardHeader>
            <CardContent className="space-y-3">
                <div>
                    <Label>Reason (optional)</Label>
                    <Textarea
                        value={reason}
                        onChange={(e) => setReason(e.target.value)}
                        placeholder="Reason for suspension/reactivation..."
                        rows={2}
                    />
                </div>
                {business.is_active ? (
                    <Button
                        variant="destructive"
                        onClick={() => handleAction('suspend')}
                    >
                        Suspend Business
                    </Button>
                ) : (
                    <Button onClick={() => handleAction('reactivate')}>
                        Reactivate Business
                    </Button>
                )}
            </CardContent>
        </Card>
    );
}

function RecentBookingsSection({
    business,
    bookings,
}: {
    business: AdminBusinessDetail;
    bookings: AdminBookingListItem[];
}) {
    function cancelBooking(bookingId: number) {
        router.post(
            `/admin/businesses/${business.id}/bookings/${bookingId}/cancel`,
            { cancellation_reason: 'Cancelled by admin' },
            { preserveScroll: true },
        );
    }

    if (bookings.length === 0) {
        return null;
    }

    return (
        <Card>
            <CardHeader>
                <CardTitle>Recent Bookings</CardTitle>
            </CardHeader>
            <CardContent>
                <div className="overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="border-b">
                                <th className="pb-2 text-left font-medium">
                                    Ref
                                </th>
                                <th className="pb-2 text-left font-medium">
                                    Customer
                                </th>
                                <th className="pb-2 text-left font-medium">
                                    Date
                                </th>
                                <th className="pb-2 text-left font-medium">
                                    Status
                                </th>
                                <th className="pb-2 text-right font-medium">
                                    Price
                                </th>
                                <th className="pb-2 text-right font-medium">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {bookings.map((booking) => (
                                <tr
                                    key={booking.id}
                                    className="border-b last:border-0"
                                >
                                    <td className="py-2 font-mono text-xs">
                                        {booking.booking_reference}
                                    </td>
                                    <td className="py-2">
                                        {booking.customer?.name ?? '—'}
                                    </td>
                                    <td className="text-muted-foreground py-2 text-xs">
                                        {new Date(
                                            booking.appointment_datetime,
                                        ).toLocaleDateString('en-GB', {
                                            day: 'numeric',
                                            month: 'short',
                                            hour: '2-digit',
                                            minute: '2-digit',
                                        })}
                                    </td>
                                    <td className="py-2">
                                        <BookingStatusBadge
                                            status={booking.status}
                                        />
                                    </td>
                                    <td className="py-2 text-right">
                                        £{booking.price}
                                    </td>
                                    <td className="py-2 text-right">
                                        {['pending', 'confirmed'].includes(
                                            booking.status,
                                        ) && (
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                className="text-red-600 hover:text-red-700"
                                                onClick={() =>
                                                    cancelBooking(booking.id)
                                                }
                                            >
                                                Cancel
                                            </Button>
                                        )}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>
    );
}

export default function AdminBusinessShow({
    business,
    recentBookings,
    stats,
    timeline,
    tiers,
    statuses,
}: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Admin', href: '/admin' },
        { title: 'Businesses', href: '/admin/businesses' },
        { title: business.name, href: `/admin/businesses/${business.id}` },
    ];

    return (
        <AdminLayout breadcrumbs={breadcrumbs}>
            <Head title={`${business.name} — Admin`} />
            <div className="flex flex-1 flex-col gap-6 p-4">
                {/* Header */}
                <div className="flex flex-wrap items-center gap-3">
                    <h1 className="text-2xl font-semibold">{business.name}</h1>
                    <VerificationBadge status={business.verification_status} />
                    {!business.is_active && (
                        <Badge variant="destructive">Suspended</Badge>
                    )}
                    <Link
                        href={`/${business.handle}`}
                        className="text-muted-foreground ml-auto flex items-center gap-1 text-sm hover:underline"
                    >
                        @{business.handle}
                        <ExternalLink className="size-3" />
                    </Link>
                </div>

                {/* Verification banner */}
                <VerifySection business={business} />

                {/* Stats */}
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <StatCard
                        label="Total Bookings"
                        value={stats.totalBookings}
                        icon={Calendar}
                    />
                    <StatCard
                        label="Customers"
                        value={stats.totalCustomers}
                        icon={Users}
                    />
                    <StatCard
                        label="Revenue"
                        value={`£${stats.totalRevenue.toFixed(2)}`}
                        icon={PoundSterling}
                    />
                    <StatCard
                        label="Page Views (7d)"
                        value={stats.pageViews7d}
                        icon={Eye}
                    />
                </div>

                <div className="grid gap-6 lg:grid-cols-3">
                    {/* Left column — info + actions */}
                    <div className="space-y-6 lg:col-span-2">
                        {/* Business info */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Business Info</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <dl className="grid gap-3 text-sm sm:grid-cols-2">
                                    <div>
                                        <dt className="text-muted-foreground">
                                            Email
                                        </dt>
                                        <dd>{business.email ?? '—'}</dd>
                                    </div>
                                    <div>
                                        <dt className="text-muted-foreground">
                                            Phone
                                        </dt>
                                        <dd>{business.phone ?? '—'}</dd>
                                    </div>
                                    <div>
                                        <dt className="text-muted-foreground">
                                            Website
                                        </dt>
                                        <dd>{business.website ?? '—'}</dd>
                                    </div>
                                    <div>
                                        <dt className="text-muted-foreground">
                                            Created
                                        </dt>
                                        <dd>
                                            {new Date(
                                                business.created_at,
                                            ).toLocaleDateString('en-GB', {
                                                day: 'numeric',
                                                month: 'long',
                                                year: 'numeric',
                                            })}
                                        </dd>
                                    </div>
                                    {business.description && (
                                        <div className="sm:col-span-2">
                                            <dt className="text-muted-foreground">
                                                Description
                                            </dt>
                                            <dd>{business.description}</dd>
                                        </div>
                                    )}
                                </dl>
                            </CardContent>
                        </Card>

                        {/* Owner */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Owner</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="flex items-center justify-between">
                                    <div className="text-sm">
                                        <p className="font-medium">
                                            {business.owner.name}
                                        </p>
                                        <p className="text-muted-foreground">
                                            {business.owner.email}
                                        </p>
                                    </div>
                                    <Button variant="outline" size="sm" asChild>
                                        <Link
                                            href={`/admin/users/${business.owner.id}`}
                                        >
                                            View User
                                        </Link>
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Stripe Connect */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <CreditCard className="size-4" />
                                    Stripe Connect
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <dl className="grid gap-3 text-sm sm:grid-cols-2">
                                    <div>
                                        <dt className="text-muted-foreground">
                                            Status
                                        </dt>
                                        <dd>
                                            {business.stripe_connect_onboarding_complete
                                                ? 'Complete'
                                                : business.stripe_connect_id
                                                  ? 'Pending'
                                                  : 'Not started'}
                                        </dd>
                                    </div>
                                    {business.stripe_connect_id && (
                                        <div>
                                            <dt className="text-muted-foreground">
                                                Connect ID
                                            </dt>
                                            <dd className="font-mono text-xs">
                                                {business.stripe_connect_id}
                                            </dd>
                                        </div>
                                    )}
                                    <div>
                                        <dt className="text-muted-foreground">
                                            Deposits
                                        </dt>
                                        <dd>
                                            {business.settings
                                                ?.deposits_enabled
                                                ? `Enabled (${business.settings?.deposit_type === 'percentage' ? `${business.settings?.deposit_percentage}%` : `£${((business.settings?.deposit_fixed_amount as number) / 100).toFixed(2)}`})`
                                                : 'Disabled'}
                                        </dd>
                                    </div>
                                </dl>
                            </CardContent>
                        </Card>

                        {/* Locations */}
                        {business.locations.length > 0 && (
                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2">
                                        <MapPin className="size-4" />
                                        Locations ({business.locations.length})
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="space-y-2">
                                        {business.locations.map((loc) => (
                                            <div
                                                key={loc.id}
                                                className="flex items-center justify-between rounded-md border p-2 text-sm"
                                            >
                                                <div>
                                                    <span className="font-medium">
                                                        {loc.name}
                                                    </span>
                                                    <span className="text-muted-foreground ml-2">
                                                        {[
                                                            loc.town,
                                                            loc.city,
                                                            loc.postcode,
                                                        ]
                                                            .filter(Boolean)
                                                            .join(', ')}
                                                    </span>
                                                </div>
                                                <div className="flex gap-1">
                                                    {loc.is_mobile && (
                                                        <Badge variant="outline">
                                                            Mobile
                                                        </Badge>
                                                    )}
                                                    {!loc.is_active && (
                                                        <Badge variant="destructive">
                                                            Inactive
                                                        </Badge>
                                                    )}
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </CardContent>
                            </Card>
                        )}

                        {/* Recent Bookings */}
                        <RecentBookingsSection
                            business={business}
                            bookings={recentBookings}
                        />
                    </div>

                    {/* Right column — actions + timeline */}
                    <div className="space-y-6">
                        <SubscriptionSection
                            business={business}
                            tiers={tiers}
                            statuses={statuses}
                        />

                        <HandleSection business={business} />

                        <SuspendSection business={business} />

                        <Card>
                            <CardHeader>
                                <CardTitle>Activity Timeline</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <ActivityTimeline events={timeline} />
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </AdminLayout>
    );
}
