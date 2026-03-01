import { Form } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import BookingManagementController from '@/actions/App/Http/Controllers/Dashboard/BookingManagementController';

type CancelBookingDialogProps = {
    handle: string;
    bookingId: number;
    open: boolean;
    onOpenChange: (open: boolean) => void;
};

export function CancelBookingDialog({
    handle,
    bookingId,
    open,
    onOpenChange,
}: CancelBookingDialogProps) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogTitle>Cancel Booking</DialogTitle>
                <DialogDescription>
                    Are you sure you want to cancel this booking? This action
                    cannot be undone.
                </DialogDescription>

                <Form
                    {...BookingManagementController.cancel.form({
                        handle,
                        booking: bookingId,
                    })}
                    options={{
                        preserveScroll: true,
                        onSuccess: () => onOpenChange(false),
                    }}
                    className="space-y-4"
                >
                    {({ processing }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="cancellation_reason">
                                    Reason (optional)
                                </Label>
                                <Textarea
                                    id="cancellation_reason"
                                    name="cancellation_reason"
                                    placeholder="Why is this booking being cancelled?"
                                    rows={3}
                                />
                            </div>

                            <DialogFooter className="gap-2">
                                <DialogClose asChild>
                                    <Button variant="secondary">
                                        Keep Booking
                                    </Button>
                                </DialogClose>
                                <Button
                                    variant="destructive"
                                    disabled={processing}
                                    asChild
                                >
                                    <button type="submit">
                                        Cancel Booking
                                    </button>
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
