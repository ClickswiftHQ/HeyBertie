import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { UpcomingBooking } from '@/types';

type UpcomingBookingsProps = {
    bookings: UpcomingBooking[];
};

function formatDateTime(datetime: string): string {
    const date = new Date(datetime);
    const now = new Date();
    const isToday = date.toDateString() === now.toDateString();

    const tomorrow = new Date(now);
    tomorrow.setDate(tomorrow.getDate() + 1);
    const isTomorrow = date.toDateString() === tomorrow.toDateString();

    const time = date.toLocaleTimeString('en-GB', {
        hour: '2-digit',
        minute: '2-digit',
    });

    if (isToday) {
        return `Today ${time}`;
    }

    if (isTomorrow) {
        return `Tomorrow ${time}`;
    }

    const day = date.toLocaleDateString('en-GB', { weekday: 'short' });
    return `${day} ${time}`;
}

function statusVariant(status: string): 'default' | 'secondary' | 'outline' {
    switch (status) {
        case 'confirmed':
            return 'default';
        case 'pending':
            return 'secondary';
        default:
            return 'outline';
    }
}

export function UpcomingBookings({ bookings }: UpcomingBookingsProps) {
    return (
        <Card>
            <CardHeader>
                <CardTitle>Upcoming Bookings</CardTitle>
            </CardHeader>
            <CardContent>
                {bookings.length === 0 ? (
                    <p className="text-muted-foreground text-sm">
                        No upcoming bookings
                    </p>
                ) : (
                    <div className="space-y-3">
                        {bookings.map((booking) => (
                            <div
                                key={booking.id}
                                className="flex items-center justify-between gap-2"
                            >
                                <div className="min-w-0 flex-1">
                                    <p className="truncate text-sm font-medium">
                                        {booking.customer.name}
                                    </p>
                                    <p className="text-muted-foreground text-xs">
                                        {booking.service.name} &middot;{' '}
                                        {formatDateTime(
                                            booking.appointment_datetime,
                                        )}
                                    </p>
                                </div>
                                <Badge variant={statusVariant(booking.status)}>
                                    {booking.status}
                                </Badge>
                            </div>
                        ))}
                    </div>
                )}
            </CardContent>
        </Card>
    );
}
