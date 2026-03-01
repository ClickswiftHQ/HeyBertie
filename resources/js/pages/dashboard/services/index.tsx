import { Head, usePage } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { ServiceFormDialog } from '@/components/dashboard/services/service-form-dialog';
import { ServiceTable } from '@/components/dashboard/services/service-table';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, ServiceItem } from '@/types';

type ServicesPageProps = {
    services: ServiceItem[];
};

export default function Services({ services }: ServicesPageProps) {
    const { currentBusiness } = usePage().props;
    const handle = currentBusiness!.handle;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: `/${handle}/dashboard` },
        { title: 'Services', href: `/${handle}/services` },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Services" />
            <div className="flex flex-1 flex-col gap-6 p-4">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">
                            Services
                        </h1>
                        <p className="text-muted-foreground text-sm">
                            Manage the services you offer to customers.
                        </p>
                    </div>
                    <ServiceFormDialog
                        handle={handle}
                        trigger={
                            <Button>
                                <Plus className="mr-2 size-4" />
                                Add Service
                            </Button>
                        }
                    />
                </div>

                <ServiceTable services={services} />
            </div>
        </AppLayout>
    );
}
