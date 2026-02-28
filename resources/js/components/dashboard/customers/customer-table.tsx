import { Badge } from '@/components/ui/badge';
import type { CustomerListItem } from '@/types';

type CustomerTableProps = {
    customers: CustomerListItem[];
};

function formatRelativeDate(iso: string | null): string {
    if (!iso) {
        return 'Never';
    }

    const date = new Date(iso);
    const now = new Date();
    const diffMs = now.getTime() - date.getTime();
    const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));

    if (diffDays === 0) {
        return 'Today';
    }
    if (diffDays === 1) {
        return 'Yesterday';
    }
    if (diffDays < 30) {
        return `${diffDays} days ago`;
    }
    if (diffDays < 365) {
        const months = Math.floor(diffDays / 30);
        return `${months} month${months > 1 ? 's' : ''} ago`;
    }

    const years = Math.floor(diffDays / 365);
    return `${years} year${years > 1 ? 's' : ''} ago`;
}

export function CustomerTable({ customers }: CustomerTableProps) {
    if (customers.length === 0) {
        return (
            <div className="text-muted-foreground rounded-lg border py-12 text-center">
                No customers found.
            </div>
        );
    }

    return (
        <div className="overflow-x-auto rounded-lg border">
            <table className="w-full text-sm">
                <thead>
                    <tr className="bg-muted/50 border-b">
                        <th className="px-4 py-3 text-left font-medium">Name</th>
                        <th className="px-4 py-3 text-left font-medium">Email</th>
                        <th className="px-4 py-3 text-left font-medium">Phone</th>
                        <th className="px-4 py-3 text-right font-medium">Bookings</th>
                        <th className="px-4 py-3 text-right font-medium">Spent</th>
                        <th className="px-4 py-3 text-left font-medium">Last Visit</th>
                        <th className="px-4 py-3 text-left font-medium">Status</th>
                    </tr>
                </thead>
                <tbody>
                    {customers.map((customer) => (
                        <tr key={customer.id} className="border-b last:border-0">
                            <td className="px-4 py-3 font-medium">{customer.name}</td>
                            <td className="text-muted-foreground px-4 py-3">
                                {customer.email ?? '—'}
                            </td>
                            <td className="text-muted-foreground px-4 py-3">
                                {customer.phone ?? '—'}
                            </td>
                            <td className="px-4 py-3 text-right">{customer.total_bookings}</td>
                            <td className="px-4 py-3 text-right">
                                £{Number(customer.total_spent).toFixed(2)}
                            </td>
                            <td className="text-muted-foreground px-4 py-3">
                                {formatRelativeDate(customer.last_visit)}
                            </td>
                            <td className="px-4 py-3">
                                <Badge variant={customer.is_active ? 'default' : 'secondary'}>
                                    {customer.is_active ? 'Active' : 'Inactive'}
                                </Badge>
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}
