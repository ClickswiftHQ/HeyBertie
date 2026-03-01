import { Head, usePage } from '@inertiajs/react';
import { SpecificDateBlocks } from '@/components/dashboard/availability/specific-date-blocks';
import { WeeklySchedule } from '@/components/dashboard/availability/weekly-schedule';
import AppLayout from '@/layouts/app-layout';
import type { AvailabilityBlockItem, BreadcrumbItem } from '@/types';

type AvailabilityPageProps = {
    weeklyBlocks: Record<string, AvailabilityBlockItem[]>;
    specificBlocks: AvailabilityBlockItem[];
};

export default function Availability({
    weeklyBlocks,
    specificBlocks,
}: AvailabilityPageProps) {
    const { currentBusiness } = usePage().props;
    const handle = currentBusiness!.handle;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: `/${handle}/dashboard` },
        { title: 'Availability', href: `/${handle}/availability` },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Availability" />
            <div className="flex flex-1 flex-col gap-6 p-4">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">
                        Availability
                    </h1>
                    <p className="text-muted-foreground text-sm">
                        Set your working hours and manage time off.
                    </p>
                </div>

                <div className="space-y-3">
                    <h2 className="text-lg font-semibold">Weekly Schedule</h2>
                    <WeeklySchedule weeklyBlocks={weeklyBlocks} />
                </div>

                <SpecificDateBlocks blocks={specificBlocks} />
            </div>
        </AppLayout>
    );
}
