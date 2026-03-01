import { Form } from '@inertiajs/react';
import { useState } from 'react';
import InputError from '@/components/input-error';
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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import ServiceController from '@/actions/App/Http/Controllers/Dashboard/ServiceController';
import type { ServiceItem } from '@/types';

type ServiceFormDialogProps = {
    handle: string;
    service?: ServiceItem;
    trigger: React.ReactNode;
};

export function ServiceFormDialog({
    handle,
    service,
    trigger,
}: ServiceFormDialogProps) {
    const [open, setOpen] = useState(false);
    const isEditing = !!service;

    const formAction = isEditing
        ? ServiceController.update.form({ handle, service: service.id })
        : ServiceController.store.form(handle);

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>{trigger}</DialogTrigger>
            <DialogContent className="sm:max-w-md">
                <DialogTitle>
                    {isEditing ? 'Edit Service' : 'Add Service'}
                </DialogTitle>
                <DialogDescription>
                    {isEditing
                        ? 'Update the service details below.'
                        : 'Fill in the details for your new service.'}
                </DialogDescription>

                <Form
                    {...formAction}
                    options={{
                        preserveScroll: true,
                        onSuccess: () => setOpen(false),
                    }}
                    resetOnSuccess
                    className="space-y-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="name">Name</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    defaultValue={service?.name ?? ''}
                                    placeholder="e.g. Full Groom"
                                    required
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="description">
                                    Description (optional)
                                </Label>
                                <Textarea
                                    id="description"
                                    name="description"
                                    defaultValue={service?.description ?? ''}
                                    placeholder="Brief description of the service"
                                    rows={2}
                                />
                                <InputError message={errors.description} />
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                <div className="grid gap-2">
                                    <Label htmlFor="duration_minutes">
                                        Duration (mins)
                                    </Label>
                                    <Input
                                        id="duration_minutes"
                                        name="duration_minutes"
                                        type="number"
                                        min={5}
                                        max={480}
                                        step={5}
                                        defaultValue={
                                            service?.duration_minutes ?? 60
                                        }
                                        required
                                    />
                                    <InputError
                                        message={errors.duration_minutes}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="price">Price</Label>
                                    <Input
                                        id="price"
                                        name="price"
                                        type="number"
                                        min={0}
                                        max={9999.99}
                                        step={0.01}
                                        defaultValue={service?.price ?? ''}
                                        placeholder="0.00"
                                    />
                                    <InputError message={errors.price} />
                                </div>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="price_type">Price Type</Label>
                                <Select
                                    name="price_type"
                                    defaultValue={
                                        service?.price_type ?? 'fixed'
                                    }
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select type" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="fixed">
                                            Fixed price
                                        </SelectItem>
                                        <SelectItem value="from">
                                            From price
                                        </SelectItem>
                                        <SelectItem value="call">
                                            Price on request
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.price_type} />
                            </div>

                            <DialogFooter className="gap-2">
                                <DialogClose asChild>
                                    <Button variant="secondary">Cancel</Button>
                                </DialogClose>
                                <Button disabled={processing} asChild>
                                    <button type="submit">
                                        {isEditing
                                            ? 'Save Changes'
                                            : 'Add Service'}
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
