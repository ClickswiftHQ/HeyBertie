import { Head, usePage } from '@inertiajs/react';
import { PlusIcon } from 'lucide-react';
import { useState } from 'react';
import { BookingDetailSheet } from '@/components/dashboard/calendar/booking-detail-sheet';
import { BookingFilters } from '@/components/dashboard/calendar/booking-filters';
import { BookingList } from '@/components/dashboard/calendar/booking-list';
import { NewBookingDialog } from '@/components/dashboard/calendar/new-booking-dialog';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import type {
    BookingDetail,
    BookingFilters as BookingFiltersType,
    BookingGroup,
    BookingStatusCounts,
    BreadcrumbItem,
    ManualBookingCustomer,
    ManualBookingLocation,
    ManualBookingService,
} from '@/types';

type CalendarPageProps = {
    bookingGroups: BookingGroup[];
    statusCounts: BookingStatusCounts;
    filters: BookingFiltersType;
    locations: ManualBookingLocation[];
    services: ManualBookingService[];
    recentCustomers: ManualBookingCustomer[];
};

export default function Calendar({
    bookingGroups,
    statusCounts,
    filters,
    locations,
    services,
    recentCustomers,
}: CalendarPageProps) {
    const { currentBusiness } = usePage().props;
    const handle = currentBusiness!.handle;

    const [selectedBooking, setSelectedBooking] =
        useState<BookingDetail | null>(null);
    const [sheetOpen, setSheetOpen] = useState(false);
    const [newBookingOpen, setNewBookingOpen] = useState(false);

    function handleSelectBooking(booking: BookingDetail) {
        setSelectedBooking(booking);
        setSheetOpen(true);
    }

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: `/${handle}/dashboard` },
        { title: 'Calendar', href: `/${handle}/calendar` },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Calendar" />
            <div className="flex flex-1 flex-col gap-6 p-4">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">
                            Calendar
                        </h1>
                        <p className="text-muted-foreground text-sm">
                            View and manage your bookings.
                        </p>
                    </div>
                    <Button onClick={() => setNewBookingOpen(true)}>
                        <PlusIcon />
                        New Booking
                    </Button>
                </div>

                <BookingFilters
                    filters={filters}
                    statusCounts={statusCounts}
                />

                <BookingList
                    bookingGroups={bookingGroups}
                    onSelectBooking={handleSelectBooking}
                />

                <BookingDetailSheet
                    booking={selectedBooking}
                    open={sheetOpen}
                    onOpenChange={setSheetOpen}
                />

                <NewBookingDialog
                    handle={handle}
                    locations={locations}
                    services={services}
                    recentCustomers={recentCustomers}
                    open={newBookingOpen}
                    onOpenChange={setNewBookingOpen}
                />
            </div>
        </AppLayout>
    );
}
