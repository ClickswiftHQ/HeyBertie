import { Head, Link, router, useForm } from '@inertiajs/react';
import {
    AlertTriangle,
    Building2,
    Calendar,
    KeyRound,
    LogIn,
    Mail,
    PawPrint,
    UserCircle,
} from 'lucide-react';
import { useState } from 'react';
import { ActivityTimeline } from '@/components/admin/activity-timeline';
import { CommunicationLog } from '@/components/admin/communication-log';
import { Badge } from '@/components/ui/badge';
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
import AdminLayout from '@/layouts/admin-layout';
import type {
    AdminCommunicationEntry,
    AdminTimelineEvent,
    AdminUserBooking,
    AdminUserBusiness,
    AdminUserDetail,
    AdminUserPet,
    AdminUserStaffMembership,
    BreadcrumbItem,
} from '@/types';

type Props = {
    user: AdminUserDetail;
    ownedBusinesses: AdminUserBusiness[];
    staffMemberships: AdminUserStaffMembership[];
    pets: AdminUserPet[];
    recentBookings: AdminUserBooking[];
    communications: AdminCommunicationEntry[];
    timeline: AdminTimelineEvent[];
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
    if (status === 'confirmed') {
        return (
            <Badge className="border-0 bg-emerald-100 text-emerald-700">
                Confirmed
            </Badge>
        );
    }
    if (status === 'cancelled') {
        return <Badge variant="destructive">Cancelled</Badge>;
    }
    if (status === 'completed') {
        return <Badge variant="secondary">Completed</Badge>;
    }
    return (
        <Badge className="border-0 bg-amber-100 text-amber-700">
            {status}
        </Badge>
    );
}

function formatDate(iso: string | null): string {
    if (!iso) return '—';
    return new Date(iso).toLocaleDateString('en-GB', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function ResetPasswordSection({ userId }: { userId: number }) {
    const form = useForm({
        password: '',
        password_confirmation: '',
    });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        form.post(`/admin/users/${userId}/reset-password`, {
            onSuccess: () => form.reset(),
        });
    }

    return (
        <Card>
            <CardHeader>
                <CardTitle className="flex items-center gap-2 text-base">
                    <KeyRound className="size-4" />
                    Reset Password
                </CardTitle>
            </CardHeader>
            <CardContent>
                <form onSubmit={handleSubmit} className="space-y-3">
                    <div>
                        <Label htmlFor="password">New Password</Label>
                        <Input
                            id="password"
                            type="password"
                            value={form.data.password}
                            onChange={(e) =>
                                form.setData('password', e.target.value)
                            }
                        />
                        {form.errors.password && (
                            <p className="mt-1 text-sm text-red-600">
                                {form.errors.password}
                            </p>
                        )}
                    </div>
                    <div>
                        <Label htmlFor="password_confirmation">
                            Confirm Password
                        </Label>
                        <Input
                            id="password_confirmation"
                            type="password"
                            value={form.data.password_confirmation}
                            onChange={(e) =>
                                form.setData(
                                    'password_confirmation',
                                    e.target.value,
                                )
                            }
                        />
                    </div>
                    <Button
                        type="submit"
                        size="sm"
                        disabled={form.processing}
                    >
                        Reset Password
                    </Button>
                </form>
            </CardContent>
        </Card>
    );
}


export default function AdminUserDetail({
    user,
    ownedBusinesses,
    staffMemberships,
    pets,
    recentBookings,
    communications,
    timeline,
}: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Admin', href: '/admin' },
        { title: 'Users', href: '/admin/users' },
        { title: user.name, href: `/admin/users/${user.id}` },
    ];

    const [activeTab, setActiveTab] = useState<
        'overview' | 'bookings' | 'communications' | 'timeline'
    >('overview');

    function handleImpersonate() {
        router.post(`/admin/users/${user.id}/impersonate`);
    }

    return (
        <AdminLayout breadcrumbs={breadcrumbs}>
            <Head title={`${user.name} — Admin`} />
            <div className="flex flex-1 flex-col gap-6 p-4">
                {/* Header */}
                <div className="flex items-start justify-between">
                    <div>
                        <div className="flex items-center gap-3">
                            <h1 className="text-2xl font-semibold">
                                {user.name}
                            </h1>
                            {user.super && (
                                <Badge className="border-0 bg-purple-100 text-purple-700">
                                    Super Admin
                                </Badge>
                            )}
                            {!user.is_registered && (
                                <Badge variant="outline">Guest</Badge>
                            )}
                        </div>
                        <p className="text-muted-foreground mt-1 text-sm">
                            {user.email}
                        </p>
                    </div>
                    {!user.super && user.is_registered && (
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={handleImpersonate}
                        >
                            <LogIn className="mr-1 size-4" />
                            Impersonate
                        </Button>
                    )}
                </div>

                {/* Tabs */}
                <div className="flex gap-1 border-b">
                    {(
                        [
                            'overview',
                            'bookings',
                            'communications',
                            'timeline',
                        ] as const
                    ).map((tab) => (
                        <button
                            key={tab}
                            className={`border-b-2 px-4 py-2 text-sm font-medium capitalize transition-colors ${
                                activeTab === tab
                                    ? 'border-foreground text-foreground'
                                    : 'text-muted-foreground border-transparent hover:border-border'
                            }`}
                            onClick={() => setActiveTab(tab)}
                        >
                            {tab}
                        </button>
                    ))}
                </div>

                {/* Tab Content */}
                {activeTab === 'overview' && (
                    <div className="grid gap-6 lg:grid-cols-3">
                        {/* Left column */}
                        <div className="space-y-6 lg:col-span-2">
                            {/* User Info */}
                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2 text-base">
                                        <UserCircle className="size-4" />
                                        User Info
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <dl className="grid grid-cols-2 gap-x-4 gap-y-3 text-sm">
                                        <div>
                                            <dt className="text-muted-foreground">
                                                Email
                                            </dt>
                                            <dd>{user.email}</dd>
                                        </div>
                                        <div>
                                            <dt className="text-muted-foreground">
                                                Role
                                            </dt>
                                            <dd className="capitalize">
                                                {user.role}
                                            </dd>
                                        </div>
                                        <div>
                                            <dt className="text-muted-foreground">
                                                Email Verified
                                            </dt>
                                            <dd>
                                                {user.email_verified_at
                                                    ? formatDate(
                                                          user.email_verified_at,
                                                      )
                                                    : 'Not verified'}
                                            </dd>
                                        </div>
                                        <div>
                                            <dt className="text-muted-foreground">
                                                2FA
                                            </dt>
                                            <dd>
                                                {user.two_factor_enabled
                                                    ? 'Enabled'
                                                    : 'Disabled'}
                                            </dd>
                                        </div>
                                        <div>
                                            <dt className="text-muted-foreground">
                                                Last Login
                                            </dt>
                                            <dd>
                                                {user.last_login
                                                    ? formatDate(
                                                          user.last_login,
                                                      )
                                                    : 'Never'}
                                            </dd>
                                        </div>
                                        <div>
                                            <dt className="text-muted-foreground">
                                                Created
                                            </dt>
                                            <dd>
                                                {formatDate(user.created_at)}
                                            </dd>
                                        </div>
                                    </dl>
                                </CardContent>
                            </Card>

                            {/* Owned Businesses */}
                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2 text-base">
                                        <Building2 className="size-4" />
                                        Owned Businesses ({ownedBusinesses.length})
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    {ownedBusinesses.length === 0 ? (
                                        <p className="text-muted-foreground text-sm">
                                            No businesses owned.
                                        </p>
                                    ) : (
                                        <div className="space-y-3">
                                            {ownedBusinesses.map((b) => (
                                                <div
                                                    key={b.id}
                                                    className="flex items-center justify-between rounded-lg border p-3"
                                                >
                                                    <div>
                                                        <Link
                                                            href={`/admin/businesses/${b.id}`}
                                                            className="font-medium hover:underline"
                                                        >
                                                            {b.name}
                                                        </Link>
                                                        <p className="text-muted-foreground text-xs">
                                                            @{b.handle}
                                                        </p>
                                                    </div>
                                                    <div className="flex items-center gap-2">
                                                        {b.subscription_tier && (
                                                            <Badge variant="secondary">
                                                                {b
                                                                    .subscription_tier
                                                                    .name}
                                                            </Badge>
                                                        )}
                                                        <VerificationBadge
                                                            status={
                                                                b.verification_status
                                                            }
                                                        />
                                                        {!b.is_active && (
                                                            <Badge variant="destructive">
                                                                Suspended
                                                            </Badge>
                                                        )}
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    )}
                                </CardContent>
                            </Card>

                            {/* Staff Memberships */}
                            {staffMemberships.length > 0 && (
                                <Card>
                                    <CardHeader>
                                        <CardTitle className="text-base">
                                            Staff Memberships (
                                            {staffMemberships.length})
                                        </CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <div className="space-y-3">
                                            {staffMemberships.map((m) => (
                                                <div
                                                    key={m.id}
                                                    className="flex items-center justify-between rounded-lg border p-3"
                                                >
                                                    <div>
                                                        <Link
                                                            href={`/admin/businesses/${m.id}`}
                                                            className="font-medium hover:underline"
                                                        >
                                                            {m.name}
                                                        </Link>
                                                        <p className="text-muted-foreground text-xs">
                                                            @{m.handle}
                                                        </p>
                                                    </div>
                                                    <div className="flex items-center gap-2">
                                                        {m.is_active ? (
                                                            <Badge
                                                                variant="secondary"
                                                            >
                                                                Active
                                                            </Badge>
                                                        ) : (
                                                            <Badge variant="outline">
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

                            {/* Pets */}
                            {pets.length > 0 && (
                                <Card>
                                    <CardHeader>
                                        <CardTitle className="flex items-center gap-2 text-base">
                                            <PawPrint className="size-4" />
                                            Pets ({pets.length})
                                        </CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <div className="space-y-2">
                                            {pets.map((p) => (
                                                <div
                                                    key={p.id}
                                                    className="flex items-center justify-between rounded-lg border p-3"
                                                >
                                                    <div>
                                                        <span className="font-medium">
                                                            {p.name}
                                                        </span>
                                                        <p className="text-muted-foreground text-xs">
                                                            {[
                                                                p.species,
                                                                p.breed,
                                                                p.size,
                                                            ]
                                                                .filter(Boolean)
                                                                .join(' · ')}
                                                        </p>
                                                    </div>
                                                    {!p.is_active && (
                                                        <Badge variant="outline">
                                                            Inactive
                                                        </Badge>
                                                    )}
                                                </div>
                                            ))}
                                        </div>
                                    </CardContent>
                                </Card>
                            )}
                        </div>

                        {/* Right column — Actions */}
                        <div className="space-y-6">
                            <ResetPasswordSection userId={user.id} />
                        </div>
                    </div>
                )}

                {activeTab === 'bookings' && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-base">
                                <Calendar className="size-4" />
                                Bookings as Customer ({recentBookings.length})
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            {recentBookings.length === 0 ? (
                                <p className="text-muted-foreground text-sm">
                                    No bookings found.
                                </p>
                            ) : (
                                <div className="overflow-x-auto">
                                    <table className="w-full text-sm">
                                        <thead>
                                            <tr className="border-b">
                                                <th className="px-3 py-2 text-left font-medium">
                                                    Reference
                                                </th>
                                                <th className="px-3 py-2 text-left font-medium">
                                                    Business
                                                </th>
                                                <th className="px-3 py-2 text-left font-medium">
                                                    Service
                                                </th>
                                                <th className="px-3 py-2 text-left font-medium">
                                                    Date
                                                </th>
                                                <th className="px-3 py-2 text-left font-medium">
                                                    Status
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {recentBookings.map((b) => (
                                                <tr
                                                    key={b.id}
                                                    className="border-b last:border-0"
                                                >
                                                    <td className="px-3 py-2 font-mono text-xs">
                                                        {b.booking_reference}
                                                    </td>
                                                    <td className="px-3 py-2">
                                                        {b.business ? (
                                                            <Link
                                                                href={`/admin/businesses/${b.business.id}`}
                                                                className="hover:underline"
                                                            >
                                                                {
                                                                    b.business
                                                                        .name
                                                                }
                                                            </Link>
                                                        ) : (
                                                            '—'
                                                        )}
                                                    </td>
                                                    <td className="text-muted-foreground px-3 py-2">
                                                        {b.service?.name ??
                                                            '—'}
                                                    </td>
                                                    <td className="text-muted-foreground px-3 py-2 text-xs">
                                                        {formatDate(
                                                            b.appointment_datetime,
                                                        )}
                                                    </td>
                                                    <td className="px-3 py-2">
                                                        <BookingStatusBadge
                                                            status={b.status}
                                                        />
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                )}

                {activeTab === 'communications' && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-base">
                                <Mail className="size-4" />
                                Communication History ({communications.length})
                            </CardTitle>
                            <CardDescription>
                                Emails and SMS sent to this user
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <CommunicationLog entries={communications} />
                        </CardContent>
                    </Card>
                )}

                {activeTab === 'timeline' && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-base">
                                <AlertTriangle className="size-4" />
                                Activity Timeline
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <ActivityTimeline events={timeline} />
                        </CardContent>
                    </Card>
                )}
            </div>
        </AdminLayout>
    );
}
