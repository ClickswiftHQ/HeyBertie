import { router, usePage } from '@inertiajs/react';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import { AvailabilityBlockFormDialog } from '@/components/dashboard/availability/availability-block-form-dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AvailabilityController from '@/actions/App/Http/Controllers/Dashboard/AvailabilityController';
import type { AvailabilityBlockItem } from '@/types';

type WeeklyScheduleProps = {
    weeklyBlocks: Record<string, AvailabilityBlockItem[]>;
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

const blockTypeVariant: Record<
    string,
    'default' | 'secondary' | 'destructive' | 'outline'
> = {
    available: 'default',
    break: 'secondary',
    blocked: 'destructive',
    holiday: 'destructive',
};

// Display Monday-Sunday
const displayOrder = [1, 2, 3, 4, 5, 6, 0];

export function WeeklySchedule({ weeklyBlocks }: WeeklyScheduleProps) {
    const handle = usePage().props.currentBusiness!.handle;

    function handleDelete(blockId: number) {
        router.delete(
            AvailabilityController.destroy.url({
                handle,
                availabilityBlock: blockId,
            }),
            { preserveScroll: true },
        );
    }

    return (
        <div className="space-y-3">
            {displayOrder.map((dayIndex) => {
                const blocks = weeklyBlocks[String(dayIndex)] ?? [];

                return (
                    <div
                        key={dayIndex}
                        className="flex items-start gap-4 rounded-lg border p-4"
                    >
                        <div className="w-24 pt-0.5 text-sm font-medium">
                            {dayNames[dayIndex]}
                        </div>
                        <div className="min-w-0 flex-1">
                            {blocks.length === 0 ? (
                                <p className="text-muted-foreground text-sm">
                                    Closed
                                </p>
                            ) : (
                                <div className="space-y-1">
                                    {blocks.map((block) => (
                                        <div
                                            key={block.id}
                                            className="flex items-center gap-2"
                                        >
                                            <span className="text-sm">
                                                {block.start_time} &ndash;{' '}
                                                {block.end_time}
                                            </span>
                                            <Badge
                                                variant={
                                                    blockTypeVariant[
                                                        block.block_type
                                                    ] ?? 'outline'
                                                }
                                            >
                                                {block.block_type}
                                            </Badge>
                                            <AvailabilityBlockFormDialog
                                                handle={handle}
                                                block={block}
                                                trigger={
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        className="size-6"
                                                    >
                                                        <Pencil className="size-3" />
                                                    </Button>
                                                }
                                            />
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                className="size-6"
                                                onClick={() =>
                                                    handleDelete(block.id)
                                                }
                                            >
                                                <Trash2 className="size-3" />
                                            </Button>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </div>
                        <AvailabilityBlockFormDialog
                            handle={handle}
                            defaultDayOfWeek={dayIndex}
                            trigger={
                                <Button variant="ghost" size="sm">
                                    <Plus className="mr-1 size-3" />
                                    Add
                                </Button>
                            }
                        />
                    </div>
                );
            })}
        </div>
    );
}
