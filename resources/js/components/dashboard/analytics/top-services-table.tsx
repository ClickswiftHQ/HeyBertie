import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import type { TopServiceItem } from '@/types';

type TopServicesTableProps = {
    services: TopServiceItem[];
};

export function TopServicesTable({ services }: TopServicesTableProps) {
    return (
        <Card>
            <CardHeader>
                <CardTitle>Top Services</CardTitle>
            </CardHeader>
            <CardContent>
                {services.length === 0 ? (
                    <p className="text-muted-foreground text-sm">
                        No service data for this period.
                    </p>
                ) : (
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="border-b">
                                <th className="pb-2 text-left font-medium">#</th>
                                <th className="pb-2 text-left font-medium">Service</th>
                                <th className="pb-2 text-right font-medium">Bookings</th>
                                <th className="pb-2 text-right font-medium">Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            {services.map((service, index) => (
                                <tr key={service.name} className="border-b last:border-0">
                                    <td className="text-muted-foreground py-2">{index + 1}</td>
                                    <td className="py-2 font-medium">{service.name}</td>
                                    <td className="py-2 text-right">{service.bookings_count}</td>
                                    <td className="py-2 text-right">
                                        £{service.revenue.toFixed(2)}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                )}
            </CardContent>
        </Card>
    );
}
