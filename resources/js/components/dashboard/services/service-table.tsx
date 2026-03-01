import { router, usePage } from '@inertiajs/react';
import {
    ArrowDown,
    ArrowUp,
    MoreHorizontal,
    Pencil,
    Power,
    Trash2,
} from 'lucide-react';
import { DeleteServiceDialog } from '@/components/dashboard/services/delete-service-dialog';
import { ServiceFormDialog } from '@/components/dashboard/services/service-form-dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import ServiceController from '@/actions/App/Http/Controllers/Dashboard/ServiceController';
import type { ServiceItem } from '@/types';

type ServiceTableProps = {
    services: ServiceItem[];
};

export function ServiceTable({ services }: ServiceTableProps) {
    const handle = usePage().props.currentBusiness!.handle;

    function handleToggleActive(service: ServiceItem) {
        router.patch(
            ServiceController.toggleActive.url({
                handle,
                service: service.id,
            }),
            {},
            { preserveScroll: true },
        );
    }

    function handleReorder(serviceId: number, direction: 'up' | 'down') {
        const currentIndex = services.findIndex((s) => s.id === serviceId);
        const swapIndex =
            direction === 'up' ? currentIndex - 1 : currentIndex + 1;

        if (swapIndex < 0 || swapIndex >= services.length) {
            return;
        }

        const newOrder = services.map((s) => s.id);
        [newOrder[currentIndex], newOrder[swapIndex]] = [
            newOrder[swapIndex],
            newOrder[currentIndex],
        ];

        router.post(
            ServiceController.reorder.url(handle),
            { order: newOrder },
            { preserveScroll: true },
        );
    }

    if (services.length === 0) {
        return (
            <div className="rounded-lg border border-dashed p-8 text-center">
                <p className="text-muted-foreground text-sm">
                    No services yet. Add your first service to get started.
                </p>
            </div>
        );
    }

    return (
        <div className="space-y-2">
            {services.map((service, index) => (
                <div
                    key={service.id}
                    className="flex items-center gap-4 rounded-lg border p-4"
                >
                    <div className="flex flex-col gap-1">
                        <Button
                            variant="ghost"
                            size="icon"
                            className="size-6"
                            disabled={index === 0}
                            onClick={() => handleReorder(service.id, 'up')}
                        >
                            <ArrowUp className="size-3" />
                        </Button>
                        <Button
                            variant="ghost"
                            size="icon"
                            className="size-6"
                            disabled={index === services.length - 1}
                            onClick={() => handleReorder(service.id, 'down')}
                        >
                            <ArrowDown className="size-3" />
                        </Button>
                    </div>

                    <div className="min-w-0 flex-1">
                        <div className="flex items-center gap-2">
                            <h3 className="font-medium">{service.name}</h3>
                            {!service.is_active && (
                                <Badge variant="secondary">Inactive</Badge>
                            )}
                            {service.is_featured && (
                                <Badge variant="outline">Featured</Badge>
                            )}
                        </div>
                        {service.description && (
                            <p className="text-muted-foreground mt-0.5 text-sm">
                                {service.description}
                            </p>
                        )}
                    </div>

                    <div className="flex items-center gap-4">
                        <Badge variant="outline">
                            {service.duration_minutes} min
                        </Badge>
                        <span className="text-sm font-medium">
                            {service.formatted_price}
                        </span>
                    </div>

                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <Button variant="ghost" size="icon">
                                <MoreHorizontal className="size-4" />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                            <ServiceFormDialog
                                handle={handle}
                                service={service}
                                trigger={
                                    <DropdownMenuItem
                                        onSelect={(e) => e.preventDefault()}
                                    >
                                        <Pencil className="mr-2 size-4" />
                                        Edit
                                    </DropdownMenuItem>
                                }
                            />
                            <DropdownMenuItem
                                onSelect={() => handleToggleActive(service)}
                            >
                                <Power className="mr-2 size-4" />
                                {service.is_active ? 'Deactivate' : 'Activate'}
                            </DropdownMenuItem>
                            <DropdownMenuSeparator />
                            <DeleteServiceDialog
                                handle={handle}
                                service={service}
                                trigger={
                                    <DropdownMenuItem
                                        variant="destructive"
                                        onSelect={(e) => e.preventDefault()}
                                    >
                                        <Trash2 className="mr-2 size-4" />
                                        Delete
                                    </DropdownMenuItem>
                                }
                            />
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            ))}
        </div>
    );
}
