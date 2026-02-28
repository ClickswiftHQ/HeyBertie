import { Head, usePage } from '@inertiajs/react';
import {
    AlertTriangle,
    Calendar,
    PoundSterling,
    UserPlus,
} from 'lucide-react';
import { BookingsChart } from '@/components/dashboard/analytics/bookings-chart';
import { BusiestDaysTable } from '@/components/dashboard/analytics/busiest-days-table';
import { PeriodSelector } from '@/components/dashboard/analytics/period-selector';
import { RevenueChart } from '@/components/dashboard/analytics/revenue-chart';
import { TopServicesTable } from '@/components/dashboard/analytics/top-services-table';
import { StatCard } from '@/components/dashboard/stat-card';
import AppLayout from '@/layouts/app-layout';
import type {
    AnalyticsPeriodStats,
    BreadcrumbItem,
    BusiestDayItem,
    ChartDataPoint,
    TopServiceItem,
} from '@/types';

type AnalyticsPageProps = {
    period: string;
    stats: AnalyticsPeriodStats;
    revenueChart: ChartDataPoint[];
    bookingsChart: ChartDataPoint[];
    topServices: TopServiceItem[];
    busiestDays: BusiestDayItem[];
};

export default function Analytics({
    period,
    stats,
    revenueChart,
    bookingsChart,
    topServices,
    busiestDays,
}: AnalyticsPageProps) {
    const { currentBusiness } = usePage().props;
    const handle = currentBusiness!.handle;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: `/${handle}/dashboard` },
        { title: 'Analytics', href: `/${handle}/analytics` },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Analytics" />
            <div className="flex flex-1 flex-col gap-6 p-4">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">
                            Analytics
                        </h1>
                        <p className="text-muted-foreground text-sm">
                            Track your business performance over time.
                        </p>
                    </div>
                    <PeriodSelector period={period} />
                </div>

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <StatCard
                        label="Revenue"
                        value={`£${stats.totalRevenue.toFixed(2)}`}
                        icon={PoundSterling}
                    />
                    <StatCard
                        label="Bookings"
                        value={stats.totalBookings}
                        icon={Calendar}
                    />
                    <StatCard
                        label="New Customers"
                        value={stats.newCustomers}
                        icon={UserPlus}
                    />
                    <StatCard
                        label="No-Show Rate"
                        value={`${stats.noShowRate}%`}
                        icon={AlertTriangle}
                    />
                </div>

                <div className="grid gap-4 lg:grid-cols-2">
                    <RevenueChart data={revenueChart} />
                    <BookingsChart data={bookingsChart} />
                </div>

                <div className="grid gap-4 lg:grid-cols-2">
                    <TopServicesTable services={topServices} />
                    <BusiestDaysTable days={busiestDays} />
                </div>
            </div>
        </AppLayout>
    );
}
