import { Head, Link, router } from '@inertiajs/react';
import { Eye, Search } from 'lucide-react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AdminLayout from '@/layouts/admin-layout';
import type {
    AdminBusinessFilters,
    AdminBusinessListItem,
    AdminTier,
    BreadcrumbItem,
    PaginatedData,
    PaginationLink,
} from '@/types';

type Props = {
    businesses: PaginatedData<AdminBusinessListItem>;
    filters: AdminBusinessFilters;
    tiers: AdminTier[];
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: '/admin' },
    { title: 'Businesses', href: '/admin/businesses' },
];

function VerificationBadge({ status }: { status: string }) {
    if (status === 'verified') {
        return (
            <Badge className="border-0 bg-emerald-100 text-emerald-700">
                Verified
            </Badge>
        );
    }
    if (status === 'rejected') {
        return <Badge variant="destructive">Rejected</Badge>;
    }
    return (
        <Badge className="border-0 bg-amber-100 text-amber-700">Pending</Badge>
    );
}

function TierBadge({ tier }: { tier: string }) {
    if (tier === 'salon') {
        return <Badge variant="default">Salon</Badge>;
    }
    if (tier === 'solo') {
        return <Badge variant="secondary">Solo</Badge>;
    }
    return <Badge variant="outline">Free</Badge>;
}

function Pagination({ links }: { links: PaginationLink[] }) {
    return (
        <div className="flex items-center justify-center gap-1">
            {links.map((link, i) => (
                <Button
                    key={i}
                    variant={link.active ? 'default' : 'outline'}
                    size="sm"
                    disabled={!link.url}
                    onClick={() => link.url && router.get(link.url)}
                    className="h-8 min-w-8"
                >
                    <span dangerouslySetInnerHTML={{ __html: link.label }} />
                </Button>
            ))}
        </div>
    );
}

export default function AdminBusinessList({
    businesses,
    filters,
    tiers,
}: Props) {
    const [search, setSearch] = useState(filters.search ?? '');

    function applyFilters(newFilters: Partial<AdminBusinessFilters>) {
        const merged = { ...filters, ...newFilters };
        // Remove empty values
        const cleaned = Object.fromEntries(
            Object.entries(merged).filter(([, v]) => v !== '' && v !== undefined),
        );
        router.get('/admin/businesses', cleaned, {
            preserveState: true,
            preserveScroll: true,
        });
    }

    function handleSearch(e: React.FormEvent) {
        e.preventDefault();
        applyFilters({ search });
    }

    return (
        <AdminLayout breadcrumbs={breadcrumbs}>
            <Head title="Businesses — Admin" />
            <div className="flex flex-1 flex-col gap-6 p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">Businesses</h1>
                    <span className="text-muted-foreground text-sm">
                        {businesses.total} total
                    </span>
                </div>

                {/* Filters */}
                <div className="flex flex-wrap items-end gap-3">
                    <form onSubmit={handleSearch} className="flex gap-2">
                        <div className="relative">
                            <Search className="text-muted-foreground absolute top-2.5 left-2.5 size-4" />
                            <Input
                                placeholder="Search name, handle, email..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                className="w-64 pl-8"
                            />
                        </div>
                        <Button type="submit" variant="secondary" size="default">
                            Search
                        </Button>
                    </form>

                    <Select
                        value={filters.verification ?? 'all'}
                        onValueChange={(v) =>
                            applyFilters({
                                verification: v === 'all' ? undefined : v,
                            })
                        }
                    >
                        <SelectTrigger className="w-40">
                            <SelectValue placeholder="Verification" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All statuses</SelectItem>
                            <SelectItem value="pending">Pending</SelectItem>
                            <SelectItem value="verified">Verified</SelectItem>
                            <SelectItem value="rejected">Rejected</SelectItem>
                        </SelectContent>
                    </Select>

                    <Select
                        value={filters.tier ?? 'all'}
                        onValueChange={(v) =>
                            applyFilters({ tier: v === 'all' ? undefined : v })
                        }
                    >
                        <SelectTrigger className="w-32">
                            <SelectValue placeholder="Tier" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All tiers</SelectItem>
                            {tiers.map((t) => (
                                <SelectItem key={t.id} value={t.slug}>
                                    {t.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>

                    <Select
                        value={filters.active ?? 'all'}
                        onValueChange={(v) =>
                            applyFilters({
                                active: v === 'all' ? undefined : v,
                            })
                        }
                    >
                        <SelectTrigger className="w-32">
                            <SelectValue placeholder="Active" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All</SelectItem>
                            <SelectItem value="1">Active</SelectItem>
                            <SelectItem value="0">Inactive</SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                {/* Table */}
                <div className="overflow-x-auto rounded-lg border">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="bg-muted/50 border-b">
                                <th className="px-4 py-3 text-left font-medium">
                                    Business
                                </th>
                                <th className="px-4 py-3 text-left font-medium">
                                    Owner
                                </th>
                                <th className="px-4 py-3 text-left font-medium">
                                    Tier
                                </th>
                                <th className="px-4 py-3 text-left font-medium">
                                    Verification
                                </th>
                                <th className="px-4 py-3 text-right font-medium">
                                    Bookings
                                </th>
                                <th className="px-4 py-3 text-left font-medium">
                                    Created
                                </th>
                                <th className="px-4 py-3 text-right font-medium">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {businesses.data.map((business) => (
                                <tr
                                    key={business.id}
                                    className="border-b last:border-0"
                                >
                                    <td className="px-4 py-3">
                                        <div>
                                            <Link
                                                href={`/admin/businesses/${business.id}`}
                                                className="font-medium hover:underline"
                                            >
                                                {business.name}
                                            </Link>
                                            <p className="text-muted-foreground text-xs">
                                                @{business.handle}
                                            </p>
                                        </div>
                                    </td>
                                    <td className="text-muted-foreground px-4 py-3 text-xs">
                                        {business.owner?.name ?? '—'}
                                        <br />
                                        {business.owner?.email ?? '—'}
                                    </td>
                                    <td className="px-4 py-3">
                                        <TierBadge
                                            tier={
                                                business.subscription_tier?.slug ?? 'free'
                                            }
                                        />
                                    </td>
                                    <td className="px-4 py-3">
                                        <div className="flex items-center gap-2">
                                            <VerificationBadge
                                                status={
                                                    business.verification_status
                                                }
                                            />
                                            {!business.is_active && (
                                                <Badge variant="destructive">
                                                    Suspended
                                                </Badge>
                                            )}
                                        </div>
                                    </td>
                                    <td className="text-muted-foreground px-4 py-3 text-right">
                                        {business.bookings_count}
                                    </td>
                                    <td className="text-muted-foreground px-4 py-3 text-xs">
                                        {new Date(
                                            business.created_at,
                                        ).toLocaleDateString('en-GB', {
                                            day: 'numeric',
                                            month: 'short',
                                            year: 'numeric',
                                        })}
                                    </td>
                                    <td className="px-4 py-3 text-right">
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            asChild
                                        >
                                            <Link
                                                href={`/admin/businesses/${business.id}`}
                                            >
                                                <Eye className="mr-1 size-4" />
                                                View
                                            </Link>
                                        </Button>
                                    </td>
                                </tr>
                            ))}
                            {businesses.data.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={7}
                                        className="text-muted-foreground px-4 py-8 text-center"
                                    >
                                        No businesses found.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                {businesses.last_page > 1 && (
                    <Pagination links={businesses.links} />
                )}
            </div>
        </AdminLayout>
    );
}
