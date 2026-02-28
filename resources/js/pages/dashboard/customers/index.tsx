import { Head, usePage } from '@inertiajs/react';
import { CustomerFilters } from '@/components/dashboard/customers/customer-filters';
import { CustomerTable } from '@/components/dashboard/customers/customer-table';
import { Pagination } from '@/components/dashboard/customers/pagination';
import AppLayout from '@/layouts/app-layout';
import type {
    BreadcrumbItem,
    CustomerFilters as CustomerFiltersType,
    CustomerListItem,
    PaginatedData,
} from '@/types';

type CustomersPageProps = {
    customers: PaginatedData<CustomerListItem>;
    filters: CustomerFiltersType;
};

export default function Customers({ customers, filters }: CustomersPageProps) {
    const { currentBusiness } = usePage().props;
    const handle = currentBusiness!.handle;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: `/${handle}/dashboard` },
        { title: 'Customers', href: `/${handle}/customers` },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Customers" />
            <div className="flex flex-1 flex-col gap-6 p-4">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">
                        Customers
                    </h1>
                    <p className="text-muted-foreground text-sm">
                        View and manage your customer base.
                    </p>
                </div>

                <CustomerFilters filters={filters} />
                <CustomerTable customers={customers.data} />
                <Pagination links={customers.links} />
            </div>
        </AppLayout>
    );
}
