import { Bar, BarChart, CartesianGrid, XAxis, YAxis } from 'recharts';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    type ChartConfig,
    ChartContainer,
    ChartTooltip,
    ChartTooltipContent,
} from '@/components/ui/chart';
import type { ChartDataPoint } from '@/types';

type BookingsChartProps = {
    data: ChartDataPoint[];
};

const chartConfig = {
    value: {
        label: 'Bookings',
        color: 'var(--chart-2)',
    },
} satisfies ChartConfig;

export function BookingsChart({ data }: BookingsChartProps) {
    return (
        <Card>
            <CardHeader>
                <CardTitle>Bookings</CardTitle>
            </CardHeader>
            <CardContent>
                {data.length === 0 ? (
                    <div className="text-muted-foreground flex h-[250px] items-center justify-center text-sm">
                        No booking data for this period.
                    </div>
                ) : (
                    <ChartContainer config={chartConfig} className="h-[250px] w-full">
                        <BarChart data={data}>
                            <CartesianGrid vertical={false} />
                            <XAxis
                                dataKey="label"
                                tickLine={false}
                                axisLine={false}
                                tickMargin={8}
                            />
                            <YAxis
                                tickLine={false}
                                axisLine={false}
                                tickMargin={8}
                                allowDecimals={false}
                            />
                            <ChartTooltip content={<ChartTooltipContent />} />
                            <Bar
                                dataKey="value"
                                fill="var(--color-value)"
                                radius={[4, 4, 0, 0]}
                            />
                        </BarChart>
                    </ChartContainer>
                )}
            </CardContent>
        </Card>
    );
}
