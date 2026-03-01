import { router, usePage } from '@inertiajs/react';
import { Plus, Trash2 } from 'lucide-react';
import { AvailabilityBlockFormDialog } from '@/components/dashboard/availability/availability-block-form-dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AvailabilityController from '@/actions/App/Http/Controllers/Dashboard/AvailabilityController';
import type { AvailabilityBlockItem } from '@/types';

type SpecificDateBlocksProps = {
    blocks: AvailabilityBlockItem[];
};

const blockTypeVariant: Record<
    string,
    'default' | 'secondary' | 'destructive' | 'outline'
> = {
    available: 'default',
    break: 'secondary',
    blocked: 'destructive',
    holiday: 'destructive',
};

export function SpecificDateBlocks({ blocks }: SpecificDateBlocksProps) {
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
            <div className="flex items-center justify-between">
                <h2 className="text-lg font-semibold">Holidays & Time Off</h2>
                <AvailabilityBlockFormDialog
                    handle={handle}
                    defaultMode="specific"
                    trigger={
                        <Button variant="outline" size="sm">
                            <Plus className="mr-1 size-3" />
                            Add Holiday / Time Off
                        </Button>
                    }
                />
            </div>

            {blocks.length === 0 ? (
                <div className="rounded-lg border border-dashed p-6 text-center">
                    <p className="text-muted-foreground text-sm">
                        No upcoming holidays or time off scheduled.
                    </p>
                </div>
            ) : (
                <div className="space-y-2">
                    {blocks.map((block) => (
                        <div
                            key={block.id}
                            className="flex items-center gap-4 rounded-lg border p-4"
                        >
                            <div className="min-w-0 flex-1">
                                <div className="flex items-center gap-2">
                                    <span className="text-sm font-medium">
                                        {block.specific_date &&
                                            new Date(
                                                block.specific_date,
                                            ).toLocaleDateString('en-GB', {
                                                weekday: 'short',
                                                day: 'numeric',
                                                month: 'short',
                                                year: 'numeric',
                                            })}
                                    </span>
                                    <span className="text-muted-foreground text-sm">
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
                                </div>
                                {block.notes && (
                                    <p className="text-muted-foreground mt-0.5 text-sm">
                                        {block.notes}
                                    </p>
                                )}
                            </div>
                            <Button
                                variant="ghost"
                                size="icon"
                                onClick={() => handleDelete(block.id)}
                            >
                                <Trash2 className="size-4" />
                            </Button>
                        </div>
                    ))}
                </div>
            )}
        </div>
    );
}
