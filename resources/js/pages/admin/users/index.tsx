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
    AdminUserFilters,
    AdminUserListItem,
    BreadcrumbItem,
    PaginatedData,
    PaginationLink,
} from '@/types';

type Props = {
    users: PaginatedData<AdminUserListItem>;
    filters: AdminUserFilters;
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: '/admin' },
    { title: 'Users', href: '/admin/users' },
];

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

export default function AdminUserList({ users, filters }: Props) {
    const [search, setSearch] = useState(filters.search ?? '');

    function applyFilters(newFilters: Partial<AdminUserFilters>) {
        const merged = { ...filters, ...newFilters };
        const cleaned = Object.fromEntries(
            Object.entries(merged).filter(
                ([, v]) => v !== '' && v !== undefined,
            ),
        );
        router.get('/admin/users', cleaned, {
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
            <Head title="Users — Admin" />
            <div className="flex flex-1 flex-col gap-6 p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">Users</h1>
                    <span className="text-muted-foreground text-sm">
                        {users.total} total
                    </span>
                </div>

                {/* Filters */}
                <div className="flex flex-wrap items-end gap-3">
                    <form onSubmit={handleSearch} className="flex gap-2">
                        <div className="relative">
                            <Search className="text-muted-foreground absolute top-2.5 left-2.5 size-4" />
                            <Input
                                placeholder="Search name, email..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                className="w-64 pl-8"
                            />
                        </div>
                        <Button
                            type="submit"
                            variant="secondary"
                            size="default"
                        >
                            Search
                        </Button>
                    </form>

                    <Select
                        value={filters.registered ?? 'all'}
                        onValueChange={(v) =>
                            applyFilters({
                                registered: v === 'all' ? undefined : v,
                            })
                        }
                    >
                        <SelectTrigger className="w-36">
                            <SelectValue placeholder="Registration" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All users</SelectItem>
                            <SelectItem value="1">Registered</SelectItem>
                            <SelectItem value="0">Guest/Stub</SelectItem>
                        </SelectContent>
                    </Select>

                    <Select
                        value={filters.super ?? 'all'}
                        onValueChange={(v) =>
                            applyFilters({
                                super: v === 'all' ? undefined : v,
                            })
                        }
                    >
                        <SelectTrigger className="w-36">
                            <SelectValue placeholder="Admin" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All</SelectItem>
                            <SelectItem value="1">Super Admin</SelectItem>
                            <SelectItem value="0">Regular</SelectItem>
                        </SelectContent>
                    </Select>

                    <Select
                        value={filters.has_businesses ?? 'all'}
                        onValueChange={(v) =>
                            applyFilters({
                                has_businesses: v === 'all' ? undefined : v,
                            })
                        }
                    >
                        <SelectTrigger className="w-40">
                            <SelectValue placeholder="Businesses" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All</SelectItem>
                            <SelectItem value="1">Has businesses</SelectItem>
                            <SelectItem value="0">No businesses</SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                {/* Table */}
                <div className="overflow-x-auto rounded-lg border">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="bg-muted/50 border-b">
                                <th className="px-4 py-3 text-left font-medium">
                                    User
                                </th>
                                <th className="px-4 py-3 text-left font-medium">
                                    Role
                                </th>
                                <th className="px-4 py-3 text-left font-medium">
                                    Status
                                </th>
                                <th className="px-4 py-3 text-right font-medium">
                                    Businesses
                                </th>
                                <th className="px-4 py-3 text-right font-medium">
                                    Pets
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
                            {users.data.map((user) => (
                                <tr
                                    key={user.id}
                                    className="border-b last:border-0"
                                >
                                    <td className="px-4 py-3">
                                        <div>
                                            <Link
                                                href={`/admin/users/${user.id}`}
                                                className="font-medium hover:underline"
                                            >
                                                {user.name}
                                            </Link>
                                            <p className="text-muted-foreground text-xs">
                                                {user.email}
                                            </p>
                                        </div>
                                    </td>
                                    <td className="text-muted-foreground px-4 py-3 text-xs">
                                        {user.role}
                                    </td>
                                    <td className="px-4 py-3">
                                        <div className="flex items-center gap-1.5">
                                            {!user.is_registered && (
                                                <Badge variant="outline">
                                                    Guest
                                                </Badge>
                                            )}
                                            {user.super && (
                                                <Badge className="border-0 bg-purple-100 text-purple-700">
                                                    Super
                                                </Badge>
                                            )}
                                        </div>
                                    </td>
                                    <td className="text-muted-foreground px-4 py-3 text-right">
                                        {user.owned_businesses_count}
                                    </td>
                                    <td className="text-muted-foreground px-4 py-3 text-right">
                                        {user.pets_count}
                                    </td>
                                    <td className="text-muted-foreground px-4 py-3 text-xs">
                                        {new Date(
                                            user.created_at,
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
                                                href={`/admin/users/${user.id}`}
                                            >
                                                <Eye className="mr-1 size-4" />
                                                View
                                            </Link>
                                        </Button>
                                    </td>
                                </tr>
                            ))}
                            {users.data.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={7}
                                        className="text-muted-foreground px-4 py-8 text-center"
                                    >
                                        No users found.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                {users.last_page > 1 && (
                    <Pagination links={users.links} />
                )}
            </div>
        </AdminLayout>
    );
}
