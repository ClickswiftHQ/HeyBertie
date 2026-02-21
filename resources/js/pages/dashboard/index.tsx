import { Head, usePage } from '@inertiajs/react';
import {
    Calendar,
    Eye,
    PoundSterling,
    Users,
} from 'lucide-react';
import { QuickActions } from '@/components/dashboard/quick-actions';
import { RecentActivity } from '@/components/dashboard/recent-activity';
import { StatCard } from '@/components/dashboard/stat-card';
import { UpcomingBookings } from '@/components/dashboard/upcoming-bookings';
import AppLayout from '@/layouts/app-layout';
import type {
    BreadcrumbItem,
    OverviewStats,
    RecentActivityItem,
    UpcomingBooking,
} from '@/types';

type DashboardProps = {
    stats: OverviewStats;
    upcomingBookings: UpcomingBooking[];
    recentActivity: RecentActivityItem[];
};

export default function Dashboard({
    stats,
    upcomingBookings,
    recentActivity,
}: DashboardProps) {
    const { currentBusiness } = usePage().props;

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Dashboard',
            href: `/${currentBusiness?.handle}/dashboard`,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <div className="flex flex-1 flex-col gap-6 p-4">
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <StatCard
                        label="Today's Bookings"
                        value={stats.todaysBookings}
                        icon={Calendar}
                        subtitle={`${stats.monthlyBookings} this month`}
                    />
                    <StatCard
                        label="Weekly Revenue"
                        value={`£${stats.weeklyRevenue.toFixed(2)}`}
                        icon={PoundSterling}
                    />
                    <StatCard
                        label="Total Customers"
                        value={stats.totalCustomers}
                        icon={Users}
                    />
                    <StatCard
                        label="Page Views (7d)"
                        value={stats.pageViews}
                        icon={Eye}
                        subtitle={
                            stats.averageRating
                                ? `${stats.averageRating} avg rating`
                                : undefined
                        }
                    />
                </div>

                <div className="grid gap-4 lg:grid-cols-3">
                    <div className="lg:col-span-2">
                        <UpcomingBookings bookings={upcomingBookings} />
                    </div>
                    <QuickActions />
                </div>

                <RecentActivity activities={recentActivity} />
            </div>
        </AppLayout>
    );
}
