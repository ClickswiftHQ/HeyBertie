import { Head, Link } from '@inertiajs/react';
import {
    AlertTriangle,
    Building2,
    Calendar,
    PoundSterling,
    ShieldCheck,
    Users,
} from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { StatCard } from '@/components/dashboard/stat-card';
import AdminLayout from '@/layouts/admin-layout';
import type {
    AdminExpiringTrial,
    AdminOverviewStats,
    AdminRecentSignup,
    BreadcrumbItem,
} from '@/types';

type AdminDashboardProps = {
    stats: AdminOverviewStats;
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: '/admin' },
];

function VerificationBadge({ status }: { status: string }) {
    if (status === 'verified') {
        return (
            <Badge className="bg-emerald-100 text-emerald-700 border-0">
                Verified
            </Badge>
        );
    }
    if (status === 'rejected') {
        return <Badge variant="destructive">Rejected</Badge>;
    }
    return (
        <Badge className="bg-amber-100 text-amber-700 border-0">Pending</Badge>
    );
}

function TierBadge({ tier }: { tier: string }) {
    if (tier === 'salon') {
        return <Badge variant="default">Salon</Badge>;
    }
    if (tier === 'solo') {
        return <Badge variant="secondary">Solo</Badge>;
    }
    return <Badge variant="outline">Free</Badge>;
}

function SubscriptionBreakdown({
    breakdown,
}: {
    breakdown: Record<string, Record<string, number>>;
}) {
    const tiers = ['free', 'solo', 'salon'];
    const statuses = ['trial', 'active', 'past_due', 'cancelled', 'suspended'];

    return (
        <Card>
            <CardHeader>
                <CardTitle>Subscription Breakdown</CardTitle>
            </CardHeader>
            <CardContent>
                <div className="overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="border-b">
                                <th className="pb-2 text-left font-medium">
                                    Tier
                                </th>
                                {statuses.map((s) => (
                                    <th
                                        key={s}
                                        className="pb-2 text-right font-medium capitalize"
                                    >
                                        {s.replace('_', ' ')}
                                    </th>
                                ))}
                            </tr>
                        </thead>
                        <tbody>
                            {tiers.map((tier) => (
                                <tr key={tier} className="border-b last:border-0">
                                    <td className="py-2 font-medium capitalize">
                                        {tier}
                                    </td>
                                    {statuses.map((status) => (
                                        <td
                                            key={status}
                                            className="text-muted-foreground py-2 text-right"
                                        >
                                            {breakdown[tier]?.[status] ?? 0}
                                        </td>
                                    ))}
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>
    );
}

function ExpiringTrials({ trials }: { trials: AdminExpiringTrial[] }) {
    if (trials.length === 0) {
        return null;
    }

    return (
        <Card>
            <CardHeader>
                <CardTitle className="flex items-center gap-2">
                    <AlertTriangle className="text-amber-500 size-4" />
                    Expiring Trials
                </CardTitle>
            </CardHeader>
            <CardContent>
                <div className="space-y-3">
                    {trials.map((trial) => (
                        <div
                            key={trial.id}
                            className="flex items-center justify-between"
                        >
                            <div>
                                <Link
                                    href={`/admin/businesses/${trial.id}`}
                                    className="font-medium hover:underline"
                                >
                                    {trial.name}
                                </Link>
                                <p className="text-muted-foreground text-xs">
                                    {trial.owner_name}
                                </p>
                            </div>
                            <span className="text-sm text-amber-600">
                                {new Date(
                                    trial.trial_ends_at,
                                ).toLocaleDateString('en-GB', {
                                    day: 'numeric',
                                    month: 'short',
                                })}
                            </span>
                        </div>
                    ))}
                </div>
            </CardContent>
        </Card>
    );
}

function RecentSignups({ signups }: { signups: AdminRecentSignup[] }) {
    if (signups.length === 0) {
        return (
            <Card>
                <CardHeader>
                    <CardTitle>Recent Signups (7 days)</CardTitle>
                </CardHeader>
                <CardContent>
                    <p className="text-muted-foreground text-sm">
                        No signups in the last 7 days.
                    </p>
                </CardContent>
            </Card>
        );
    }

    return (
        <Card>
            <CardHeader>
                <CardTitle>Recent Signups (7 days)</CardTitle>
            </CardHeader>
            <CardContent>
                <div className="space-y-3">
                    {signups.map((signup) => (
                        <div
                            key={signup.id}
                            className="flex items-center justify-between gap-4"
                        >
                            <div className="min-w-0 flex-1">
                                <Link
                                    href={`/admin/businesses/${signup.id}`}
                                    className="font-medium hover:underline"
                                >
                                    {signup.name}
                                </Link>
                                <p className="text-muted-foreground text-xs">
                                    @{signup.handle} &middot; {signup.owner_name}
                                </p>
                            </div>
                            <div className="flex items-center gap-2">
                                <TierBadge tier={signup.tier} />
                                <VerificationBadge
                                    status={signup.verification_status}
                                />
                            </div>
                        </div>
                    ))}
                </div>
            </CardContent>
        </Card>
    );
}

function PendingVerifications({
    count,
}: {
    count: number;
}) {
    if (count === 0) {
        return null;
    }

    return (
        <Card className="border-amber-200 bg-amber-50 dark:border-amber-900 dark:bg-amber-950">
            <CardContent className="flex items-center justify-between pt-4">
                <div className="flex items-center gap-3">
                    <div className="flex size-10 items-center justify-center rounded-lg bg-amber-100 dark:bg-amber-900">
                        <ShieldCheck className="size-5 text-amber-600 dark:text-amber-400" />
                    </div>
                    <div>
                        <p className="font-medium">
                            {count} business{count !== 1 ? 'es' : ''} pending
                            verification
                        </p>
                        <p className="text-muted-foreground text-sm">
                            Review documents and approve or reject
                        </p>
                    </div>
                </div>
                <Link
                    href="/admin/businesses?verification=pending"
                    className="text-sm font-medium hover:underline"
                >
                    Review
                </Link>
            </CardContent>
        </Card>
    );
}

export default function AdminDashboard({ stats }: AdminDashboardProps) {
    return (
        <AdminLayout breadcrumbs={breadcrumbs}>
            <Head title="Admin Dashboard" />
            <div className="flex flex-1 flex-col gap-6 p-4">
                <PendingVerifications count={stats.pendingVerifications} />

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <StatCard
                        label="Total Businesses"
                        value={stats.totalBusinesses}
                        icon={Building2}
                        subtitle={`${stats.verifiedBusinesses} verified`}
                    />
                    <StatCard
                        label="MRR"
                        value={`£${stats.mrr.toFixed(2)}`}
                        icon={PoundSterling}
                    />
                    <StatCard
                        label="Total Users"
                        value={stats.totalUsers}
                        icon={Users}
                        subtitle={`${stats.registeredUsers} registered`}
                    />
                    <StatCard
                        label="Bookings (Month)"
                        value={stats.monthlyBookings}
                        icon={Calendar}
                        subtitle={`${stats.todaysBookings} today`}
                    />
                </div>

                <div className="grid gap-4 lg:grid-cols-3">
                    <div className="lg:col-span-2">
                        <RecentSignups signups={stats.recentSignups} />
                    </div>
                    <div className="space-y-4">
                        <ExpiringTrials trials={stats.expiringTrials} />
                        <SubscriptionBreakdown
                            breakdown={stats.subscriptionBreakdown}
                        />
                    </div>
                </div>
            </div>
        </AdminLayout>
    );
}
