import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import type { BusiestDayItem } from '@/types';

type BusiestDaysTableProps = {
    days: BusiestDayItem[];
};

export function BusiestDaysTable({ days }: BusiestDaysTableProps) {
    return (
        <Card>
            <CardHeader>
                <CardTitle>Busiest Days</CardTitle>
            </CardHeader>
            <CardContent>
                {days.length === 0 ? (
                    <p className="text-muted-foreground text-sm">
                        No booking data for this period.
                    </p>
                ) : (
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="border-b">
                                <th className="pb-2 text-left font-medium">Day</th>
                                <th className="pb-2 text-right font-medium">Bookings</th>
                            </tr>
                        </thead>
                        <tbody>
                            {days.map((day) => (
                                <tr key={day.day} className="border-b last:border-0">
                                    <td className="py-2 font-medium">{day.day}</td>
                                    <td className="py-2 text-right">{day.bookings_count}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                )}
            </CardContent>
        </Card>
    );
}
