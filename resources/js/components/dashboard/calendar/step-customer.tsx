import { useCallback, useEffect, useRef, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import CustomerSearchController from '@/actions/App/Http/Controllers/Dashboard/CustomerSearchController';
import type { ManualBookingCustomer } from '@/types';

type StepCustomerProps = {
    handle: string;
    recentCustomers: ManualBookingCustomer[];
    selectedCustomer: ManualBookingCustomer | null;
    isNewCustomer: boolean;
    newCustomerData: { name: string; email: string; phone: string };
    onSelectCustomer: (customer: ManualBookingCustomer | null) => void;
    onToggleNewCustomer: (isNew: boolean) => void;
    onUpdateNewCustomer: (data: {
        name: string;
        email: string;
        phone: string;
    }) => void;
    errors: Record<string, string>;
};

export function StepCustomer({
    handle,
    recentCustomers,
    selectedCustomer,
    isNewCustomer,
    newCustomerData,
    onSelectCustomer,
    onToggleNewCustomer,
    onUpdateNewCustomer,
    errors,
}: StepCustomerProps) {
    const [searchQuery, setSearchQuery] = useState('');
    const [searchResults, setSearchResults] = useState<ManualBookingCustomer[]>(
        [],
    );
    const [isSearching, setIsSearching] = useState(false);
    const debounceRef = useRef<ReturnType<typeof setTimeout> | null>(null);

    const searchCustomers = useCallback(
        async (query: string) => {
            if (query.length < 2) {
                setSearchResults([]);
                return;
            }

            setIsSearching(true);
            try {
                const url = CustomerSearchController.url(handle, {
                    query: { q: query },
                });
                const response = await fetch(url);
                const data = await response.json();
                setSearchResults(data);
            } finally {
                setIsSearching(false);
            }
        },
        [handle],
    );

    useEffect(() => {
        if (debounceRef.current) {
            clearTimeout(debounceRef.current);
        }

        debounceRef.current = setTimeout(() => {
            searchCustomers(searchQuery);
        }, 300);

        return () => {
            if (debounceRef.current) {
                clearTimeout(debounceRef.current);
            }
        };
    }, [searchQuery, searchCustomers]);

    const displayCustomers =
        searchQuery.length >= 2 ? searchResults : recentCustomers;

    if (isNewCustomer) {
        return (
            <div className="space-y-4">
                <div className="flex items-center justify-between">
                    <h3 className="text-sm font-medium">New Customer</h3>
                    <Button
                        variant="ghost"
                        size="sm"
                        type="button"
                        onClick={() => onToggleNewCustomer(false)}
                    >
                        Search existing
                    </Button>
                </div>

                <div className="grid gap-3">
                    <div className="grid gap-1.5">
                        <Label htmlFor="customer-name">Name</Label>
                        <Input
                            id="customer-name"
                            value={newCustomerData.name}
                            onChange={(e) =>
                                onUpdateNewCustomer({
                                    ...newCustomerData,
                                    name: e.target.value,
                                })
                            }
                            placeholder="Customer name"
                            aria-invalid={!!errors.name}
                        />
                        {errors.name && (
                            <p className="text-destructive text-xs">
                                {errors.name}
                            </p>
                        )}
                    </div>

                    <div className="grid gap-1.5">
                        <Label htmlFor="customer-email">Email</Label>
                        <Input
                            id="customer-email"
                            type="email"
                            value={newCustomerData.email}
                            onChange={(e) =>
                                onUpdateNewCustomer({
                                    ...newCustomerData,
                                    email: e.target.value,
                                })
                            }
                            placeholder="customer@example.com"
                            aria-invalid={!!errors.email}
                        />
                        {errors.email && (
                            <p className="text-destructive text-xs">
                                {errors.email}
                            </p>
                        )}
                    </div>

                    <div className="grid gap-1.5">
                        <Label htmlFor="customer-phone">Phone</Label>
                        <Input
                            id="customer-phone"
                            type="tel"
                            value={newCustomerData.phone}
                            onChange={(e) =>
                                onUpdateNewCustomer({
                                    ...newCustomerData,
                                    phone: e.target.value,
                                })
                            }
                            placeholder="07700 900000"
                            aria-invalid={!!errors.phone}
                        />
                        {errors.phone && (
                            <p className="text-destructive text-xs">
                                {errors.phone}
                            </p>
                        )}
                    </div>
                </div>
            </div>
        );
    }

    return (
        <div className="space-y-4">
            <div className="flex items-center justify-between">
                <h3 className="text-sm font-medium">Select Customer</h3>
                <Button
                    variant="ghost"
                    size="sm"
                    type="button"
                    onClick={() => onToggleNewCustomer(true)}
                >
                    New customer
                </Button>
            </div>

            <Input
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                placeholder="Search by name, email, or phone..."
            />

            {errors.customer_id && (
                <p className="text-destructive text-xs">{errors.customer_id}</p>
            )}

            <div className="max-h-48 space-y-1 overflow-y-auto">
                {isSearching && (
                    <p className="text-muted-foreground py-2 text-center text-sm">
                        Searching...
                    </p>
                )}

                {!isSearching && displayCustomers.length === 0 && (
                    <p className="text-muted-foreground py-2 text-center text-sm">
                        {searchQuery.length >= 2
                            ? 'No customers found.'
                            : 'No recent customers.'}
                    </p>
                )}

                {!isSearching &&
                    displayCustomers.map((customer) => (
                        <button
                            key={customer.id}
                            type="button"
                            onClick={() => onSelectCustomer(customer)}
                            className={`hover:bg-accent w-full rounded-md border p-3 text-left transition-colors ${
                                selectedCustomer?.id === customer.id
                                    ? 'border-primary bg-primary/5'
                                    : 'border-transparent'
                            }`}
                        >
                            <div className="text-sm font-medium">
                                {customer.name}
                            </div>
                            <div className="text-muted-foreground text-xs">
                                {[customer.email, customer.phone]
                                    .filter(Boolean)
                                    .join(' · ')}
                            </div>
                        </button>
                    ))}
            </div>
        </div>
    );
}
