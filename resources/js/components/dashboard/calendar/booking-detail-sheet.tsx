import { router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { CancelBookingDialog } from '@/components/dashboard/calendar/cancel-booking-dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { Textarea } from '@/components/ui/textarea';
import BookingManagementController from '@/actions/App/Http/Controllers/Dashboard/BookingManagementController';
import type { BookingDetail } from '@/types';

type BookingDetailSheetProps = {
    booking: BookingDetail | null;
    open: boolean;
    onOpenChange: (open: boolean) => void;
};

const statusVariant: Record<
    string,
    'default' | 'secondary' | 'destructive' | 'outline'
> = {
    pending: 'outline',
    confirmed: 'default',
    completed: 'secondary',
    cancelled: 'destructive',
    no_show: 'destructive',
};

export function BookingDetailSheet({
    booking,
    open,
    onOpenChange,
}: BookingDetailSheetProps) {
    const handle = usePage().props.currentBusiness!.handle;
    const [cancelOpen, setCancelOpen] = useState(false);
    const [notes, setNotes] = useState(booking?.pro_notes ?? '');

    function handleAction(
        action: 'confirm' | 'complete' | 'noShow',
    ) {
        if (!booking) {
            return;
        }

        const urlFn = {
            confirm: BookingManagementController.confirm.url,
            complete: BookingManagementController.complete.url,
            noShow: BookingManagementController.noShow.url,
        }[action];

        router.patch(
            urlFn({ handle, booking: booking.id }),
            {},
            { preserveScroll: true },
        );
    }

    function handleSaveNotes() {
        if (!booking) {
            return;
        }

        router.patch(
            BookingManagementController.updateNotes.url({
                handle,
                booking: booking.id,
            }),
            { pro_notes: notes },
            { preserveScroll: true },
        );
    }

    if (!booking) {
        return null;
    }

    return (
        <>
            <Sheet open={open} onOpenChange={onOpenChange}>
                <SheetContent className="overflow-y-auto">
                    <SheetHeader>
                        <SheetTitle>{booking.booking_reference}</SheetTitle>
                        <SheetDescription>
                            {new Date(
                                booking.appointment_datetime,
                            ).toLocaleDateString('en-GB', {
                                weekday: 'long',
                                day: 'numeric',
                                month: 'long',
                                year: 'numeric',
                            })}{' '}
                            at {booking.time}
                        </SheetDescription>
                    </SheetHeader>

                    <div className="space-y-6 p-4">
                        <div className="flex items-center gap-2">
                            <Badge
                                variant={
                                    statusVariant[booking.status] ?? 'outline'
                                }
                            >
                                {booking.status.replace('_', ' ')}
                            </Badge>
                            <span className="text-sm">
                                {booking.duration_minutes} min &middot; &pound;
                                {booking.price}
                            </span>
                        </div>

                        <div className="space-y-3">
                            <h4 className="text-sm font-medium">Customer</h4>
                            <div className="text-sm">
                                <p>{booking.customer?.name ?? 'Unknown'}</p>
                                {booking.customer?.email && (
                                    <p className="text-muted-foreground">
                                        {booking.customer.email}
                                    </p>
                                )}
                                {booking.customer?.phone && (
                                    <p className="text-muted-foreground">
                                        {booking.customer.phone}
                                    </p>
                                )}
                            </div>
                        </div>

                        <div className="space-y-3">
                            <h4 className="text-sm font-medium">Service</h4>
                            <p className="text-sm">
                                {booking.service?.name ?? 'N/A'}
                            </p>
                        </div>

                        {(booking.pet_name ||
                            booking.pet_breed ||
                            booking.pet_size) && (
                            <div className="space-y-3">
                                <h4 className="text-sm font-medium">Pet</h4>
                                <div className="text-sm">
                                    {booking.pet_name && (
                                        <p>{booking.pet_name}</p>
                                    )}
                                    {booking.pet_breed && (
                                        <p className="text-muted-foreground">
                                            {booking.pet_breed}
                                        </p>
                                    )}
                                    {booking.pet_size && (
                                        <p className="text-muted-foreground capitalize">
                                            {booking.pet_size}
                                        </p>
                                    )}
                                </div>
                            </div>
                        )}

                        {booking.customer_notes && (
                            <div className="space-y-3">
                                <h4 className="text-sm font-medium">
                                    Customer Notes
                                </h4>
                                <p className="text-muted-foreground text-sm">
                                    {booking.customer_notes}
                                </p>
                            </div>
                        )}

                        {booking.staff_member && (
                            <div className="space-y-3">
                                <h4 className="text-sm font-medium">
                                    Staff Member
                                </h4>
                                <p className="text-sm">
                                    {booking.staff_member.name}
                                </p>
                            </div>
                        )}

                        <div className="space-y-3">
                            <Label htmlFor="pro_notes">Your Notes</Label>
                            <Textarea
                                id="pro_notes"
                                value={notes}
                                onChange={(e) => setNotes(e.target.value)}
                                placeholder="Add private notes about this booking..."
                                rows={3}
                            />
                            <Button
                                size="sm"
                                variant="outline"
                                onClick={handleSaveNotes}
                                disabled={notes === (booking.pro_notes ?? '')}
                            >
                                Save Notes
                            </Button>
                        </div>

                        {!['cancelled', 'completed', 'no_show'].includes(
                            booking.status,
                        ) && (
                            <div className="space-y-2 border-t pt-4">
                                <h4 className="text-sm font-medium">
                                    Actions
                                </h4>
                                <div className="flex flex-wrap gap-2">
                                    {booking.status === 'pending' && (
                                        <Button
                                            size="sm"
                                            onClick={() =>
                                                handleAction('confirm')
                                            }
                                        >
                                            Confirm
                                        </Button>
                                    )}
                                    {['pending', 'confirmed'].includes(
                                        booking.status,
                                    ) && (
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            onClick={() =>
                                                handleAction('complete')
                                            }
                                        >
                                            Mark Completed
                                        </Button>
                                    )}
                                    {['pending', 'confirmed'].includes(
                                        booking.status,
                                    ) && (
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            onClick={() =>
                                                handleAction('noShow')
                                            }
                                        >
                                            No Show
                                        </Button>
                                    )}
                                    {booking.can_be_cancelled && (
                                        <Button
                                            size="sm"
                                            variant="destructive"
                                            onClick={() =>
                                                setCancelOpen(true)
                                            }
                                        >
                                            Cancel
                                        </Button>
                                    )}
                                </div>
                            </div>
                        )}
                    </div>
                </SheetContent>
            </Sheet>

            <CancelBookingDialog
                handle={handle}
                bookingId={booking.id}
                open={cancelOpen}
                onOpenChange={setCancelOpen}
            />
        </>
    );
}
