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
import AvailabilityController from '@/actions/App/Http/Controllers/Dashboard/AvailabilityController';
import type { AvailabilityBlockItem } from '@/types';

type AvailabilityBlockFormDialogProps = {
    handle: string;
    block?: AvailabilityBlockItem;
    defaultDayOfWeek?: number;
    defaultMode?: 'weekly' | 'specific';
    trigger: React.ReactNode;
};

const dayNames = [
    'Sunday',
    'Monday',
    'Tuesday',
    'Wednesday',
    'Thursday',
    'Friday',
    'Saturday',
];

export function AvailabilityBlockFormDialog({
    handle,
    block,
    defaultDayOfWeek,
    defaultMode = 'weekly',
    trigger,
}: AvailabilityBlockFormDialogProps) {
    const [open, setOpen] = useState(false);
    const isEditing = !!block;

    const [mode, setMode] = useState<'weekly' | 'specific'>(
        block?.specific_date ? 'specific' : defaultMode,
    );

    const formAction = isEditing
        ? AvailabilityController.update.form({
              handle,
              availabilityBlock: block.id,
          })
        : AvailabilityController.store.form(handle);

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>{trigger}</DialogTrigger>
            <DialogContent className="sm:max-w-md">
                <DialogTitle>
                    {isEditing ? 'Edit Block' : 'Add Availability Block'}
                </DialogTitle>
                <DialogDescription>
                    {isEditing
                        ? 'Update the availability block details.'
                        : 'Set up a new availability block.'}
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
                                <Label htmlFor="block_type">Type</Label>
                                <Select
                                    name="block_type"
                                    defaultValue={
                                        block?.block_type ?? 'available'
                                    }
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select type" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="available">
                                            Available
                                        </SelectItem>
                                        <SelectItem value="break">
                                            Break
                                        </SelectItem>
                                        <SelectItem value="blocked">
                                            Blocked
                                        </SelectItem>
                                        <SelectItem value="holiday">
                                            Holiday
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.block_type} />
                            </div>

                            {!isEditing && (
                                <div className="flex gap-2">
                                    <Button
                                        type="button"
                                        variant={
                                            mode === 'weekly'
                                                ? 'default'
                                                : 'outline'
                                        }
                                        size="sm"
                                        onClick={() => setMode('weekly')}
                                    >
                                        Weekly
                                    </Button>
                                    <Button
                                        type="button"
                                        variant={
                                            mode === 'specific'
                                                ? 'default'
                                                : 'outline'
                                        }
                                        size="sm"
                                        onClick={() => setMode('specific')}
                                    >
                                        Specific Date
                                    </Button>
                                </div>
                            )}

                            {mode === 'weekly' ? (
                                <div className="grid gap-2">
                                    <Label htmlFor="day_of_week">Day</Label>
                                    <Select
                                        name="day_of_week"
                                        defaultValue={String(
                                            block?.day_of_week ??
                                                defaultDayOfWeek ??
                                                1,
                                        )}
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Select day" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {dayNames.map((name, i) => (
                                                <SelectItem
                                                    key={i}
                                                    value={String(i)}
                                                >
                                                    {name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError
                                        message={errors.day_of_week}
                                    />
                                </div>
                            ) : (
                                <div className="grid gap-2">
                                    <Label htmlFor="specific_date">Date</Label>
                                    <Input
                                        id="specific_date"
                                        name="specific_date"
                                        type="date"
                                        defaultValue={
                                            block?.specific_date ?? ''
                                        }
                                    />
                                    <InputError
                                        message={errors.specific_date}
                                    />
                                </div>
                            )}

                            <div className="grid grid-cols-2 gap-4">
                                <div className="grid gap-2">
                                    <Label htmlFor="start_time">
                                        Start Time
                                    </Label>
                                    <Input
                                        id="start_time"
                                        name="start_time"
                                        type="time"
                                        defaultValue={
                                            block?.start_time ?? '09:00'
                                        }
                                        required
                                    />
                                    <InputError message={errors.start_time} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="end_time">End Time</Label>
                                    <Input
                                        id="end_time"
                                        name="end_time"
                                        type="time"
                                        defaultValue={
                                            block?.end_time ?? '17:00'
                                        }
                                        required
                                    />
                                    <InputError message={errors.end_time} />
                                </div>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="notes">Notes (optional)</Label>
                                <Input
                                    id="notes"
                                    name="notes"
                                    defaultValue={block?.notes ?? ''}
                                    placeholder="e.g. Bank holiday"
                                />
                                <InputError message={errors.notes} />
                            </div>

                            <DialogFooter className="gap-2">
                                <DialogClose asChild>
                                    <Button variant="secondary">Cancel</Button>
                                </DialogClose>
                                <Button disabled={processing} asChild>
                                    <button type="submit">
                                        {isEditing
                                            ? 'Save Changes'
                                            : 'Add Block'}
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
