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
    DialogTrigger,
} from '@/components/ui/dialog';
import ServiceController from '@/actions/App/Http/Controllers/Dashboard/ServiceController';
import type { ServiceItem } from '@/types';

type DeleteServiceDialogProps = {
    handle: string;
    service: ServiceItem;
    trigger: React.ReactNode;
};

export function DeleteServiceDialog({
    handle,
    service,
    trigger,
}: DeleteServiceDialogProps) {
    const [open, setOpen] = useState(false);

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>{trigger}</DialogTrigger>
            <DialogContent>
                <DialogTitle>Delete Service</DialogTitle>
                <DialogDescription>
                    Are you sure you want to delete &quot;{service.name}&quot;?
                    {service.bookings_count > 0 && (
                        <span className="mt-1 block font-medium text-amber-600">
                            This service has {service.bookings_count} booking
                            {service.bookings_count !== 1 ? 's' : ''}.
                            Existing bookings will not be affected.
                        </span>
                    )}
                </DialogDescription>

                <Form
                    {...ServiceController.destroy.form({
                        handle,
                        service: service.id,
                    })}
                    options={{
                        preserveScroll: true,
                        onSuccess: () => setOpen(false),
                    }}
                >
                    {({ processing }) => (
                        <DialogFooter className="gap-2">
                            <DialogClose asChild>
                                <Button variant="secondary">Cancel</Button>
                            </DialogClose>
                            <Button
                                variant="destructive"
                                disabled={processing}
                                asChild
                            >
                                <button type="submit">Delete Service</button>
                            </Button>
                        </DialogFooter>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
