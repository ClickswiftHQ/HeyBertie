import { Badge } from '@/components/ui/badge';
import type { BookingDetail, BookingGroup } from '@/types';

type BookingListProps = {
    bookingGroups: BookingGroup[];
    onSelectBooking: (booking: BookingDetail) => void;
};

const statusVariant: Record<string, 'default' | 'secondary' | 'destructive' | 'outline'> = {
    pending: 'outline',
    confirmed: 'default',
    completed: 'secondary',
    cancelled: 'destructive',
    no_show: 'destructive',
};

export function BookingList({ bookingGroups, onSelectBooking }: BookingListProps) {
    if (bookingGroups.length === 0) {
        return (
            <div className="rounded-lg border border-dashed p-8 text-center">
                <p className="text-muted-foreground text-sm">
                    No bookings found for the selected period.
                </p>
            </div>
        );
    }

    return (
        <div className="space-y-6">
            {bookingGroups.map((group) => (
                <div key={group.date}>
                    <h3 className="mb-2 text-sm font-medium text-muted-foreground">
                        {group.formatted_date}
                    </h3>
                    <div className="space-y-2">
                        {group.bookings.map((booking) => (
                            <button
                                key={booking.id}
                                type="button"
                                className="flex w-full items-center gap-4 rounded-lg border p-4 text-left transition-colors hover:bg-accent"
                                onClick={() => onSelectBooking(booking)}
                            >
                                <span className="w-14 text-sm font-medium">
                                    {booking.time}
                                </span>
                                <div className="min-w-0 flex-1">
                                    <div className="flex items-center gap-2">
                                        <span className="font-medium">
                                            {booking.customer?.name ?? 'Unknown'}
                                        </span>
                                        {booking.pet_name && (
                                            <span className="text-muted-foreground text-sm">
                                                ({booking.pet_name})
                                            </span>
                                        )}
                                    </div>
                                    <p className="text-muted-foreground text-sm">
                                        {booking.service?.name ?? 'Service'}
                                        {' '}
                                        &middot; {booking.duration_minutes} min
                                    </p>
                                </div>
                                <Badge variant={statusVariant[booking.status] ?? 'outline'}>
                                    {booking.status.replace('_', ' ')}
                                </Badge>
                            </button>
                        ))}
                    </div>
                </div>
            ))}
        </div>
    );
}
