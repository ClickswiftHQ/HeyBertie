import { router, usePage } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import BookingManagementController from '@/actions/App/Http/Controllers/Dashboard/BookingManagementController';
import type { BookingFilters as BookingFiltersType, BookingStatusCounts } from '@/types';

type BookingFiltersProps = {
    filters: BookingFiltersType;
    statusCounts: BookingStatusCounts;
};

const statusTabs = [
    { key: 'all', label: 'All' },
    { key: 'pending', label: 'Pending' },
    { key: 'confirmed', label: 'Confirmed' },
    { key: 'completed', label: 'Completed' },
] as const;

export function BookingFilters({ filters, statusCounts }: BookingFiltersProps) {
    const handle = usePage().props.currentBusiness!.handle;

    function navigate(params: Partial<BookingFiltersType>) {
        const merged = { ...filters, ...params };

        router.get(
            BookingManagementController.index.url(handle),
            {
                status: merged.status === 'all' ? undefined : merged.status,
                from: merged.from,
                to: merged.to,
            },
            { preserveState: true, preserveScroll: true },
        );
    }

    function setDateRange(from: string, to: string) {
        navigate({ from, to });
    }

    const today = new Date().toISOString().split('T')[0];
    const in7Days = new Date(Date.now() + 7 * 86400000)
        .toISOString()
        .split('T')[0];

    return (
        <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div className="flex gap-1">
                {statusTabs.map((tab) => (
                    <Button
                        key={tab.key}
                        variant={
                            filters.status === tab.key ? 'default' : 'outline'
                        }
                        size="sm"
                        onClick={() => navigate({ status: tab.key })}
                    >
                        {tab.label}
                        <Badge
                            variant={
                                filters.status === tab.key
                                    ? 'secondary'
                                    : 'outline'
                            }
                            className="ml-1.5"
                        >
                            {statusCounts[tab.key]}
                        </Badge>
                    </Button>
                ))}
            </div>

            <div className="flex items-center gap-2">
                <Button
                    variant="outline"
                    size="sm"
                    onClick={() => setDateRange(today, today)}
                >
                    Today
                </Button>
                <Button
                    variant="outline"
                    size="sm"
                    onClick={() => setDateRange(today, in7Days)}
                >
                    Next 7 Days
                </Button>
                <Input
                    type="date"
                    value={filters.from}
                    onChange={(e) => navigate({ from: e.target.value })}
                    className="w-auto"
                />
                <span className="text-muted-foreground text-sm">to</span>
                <Input
                    type="date"
                    value={filters.to}
                    onChange={(e) => navigate({ to: e.target.value })}
                    className="w-auto"
                />
            </div>
        </div>
    );
}
