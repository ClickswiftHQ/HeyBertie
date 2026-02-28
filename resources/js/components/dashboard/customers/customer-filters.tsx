import { useEffect, useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { CustomerFilters as CustomerFiltersType } from '@/types';

type CustomerFiltersProps = {
    filters: CustomerFiltersType;
};

export function CustomerFilters({ filters }: CustomerFiltersProps) {
    const handle = usePage().props.currentBusiness!.handle;
    const [search, setSearch] = useState(filters.search);

    useEffect(() => {
        setSearch(filters.search);
    }, [filters.search]);

    useEffect(() => {
        const timeout = setTimeout(() => {
            if (search !== filters.search) {
                navigate({ search });
            }
        }, 300);

        return () => clearTimeout(timeout);
    }, [search]);

    function navigate(params: Partial<CustomerFiltersType>) {
        const merged = { ...filters, ...params };

        router.get(
            `/${handle}/customers`,
            {
                search: merged.search || undefined,
                status: merged.status === 'all' ? undefined : merged.status,
            },
            { preserveState: true, preserveScroll: true },
        );
    }

    return (
        <div className="flex flex-col gap-4 sm:flex-row sm:items-center">
            <Input
                type="search"
                placeholder="Search by name, email, or phone..."
                value={search}
                onChange={(e) => setSearch(e.target.value)}
                className="sm:max-w-xs"
            />
            <Select
                value={filters.status}
                onValueChange={(value) => navigate({ status: value })}
            >
                <SelectTrigger className="w-[140px]">
                    <SelectValue placeholder="Status" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">All</SelectItem>
                    <SelectItem value="active">Active</SelectItem>
                    <SelectItem value="inactive">Inactive</SelectItem>
                </SelectContent>
            </Select>
        </div>
    );
}
