import { Area, AreaChart, CartesianGrid, XAxis, YAxis } from 'recharts';
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

type RevenueChartProps = {
    data: ChartDataPoint[];
};

const chartConfig = {
    value: {
        label: 'Revenue',
        color: 'var(--chart-1)',
    },
} satisfies ChartConfig;

export function RevenueChart({ data }: RevenueChartProps) {
    return (
        <Card>
            <CardHeader>
                <CardTitle>Revenue</CardTitle>
            </CardHeader>
            <CardContent>
                {data.length === 0 ? (
                    <div className="text-muted-foreground flex h-[250px] items-center justify-center text-sm">
                        No revenue data for this period.
                    </div>
                ) : (
                    <ChartContainer config={chartConfig} className="h-[250px] w-full">
                        <AreaChart data={data}>
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
                                tickFormatter={(v) => `£${v}`}
                            />
                            <ChartTooltip
                                content={
                                    <ChartTooltipContent
                                        formatter={(value) => `£${Number(value).toFixed(2)}`}
                                    />
                                }
                            />
                            <Area
                                type="monotone"
                                dataKey="value"
                                fill="var(--color-value)"
                                fillOpacity={0.2}
                                stroke="var(--color-value)"
                                strokeWidth={2}
                            />
                        </AreaChart>
                    </ChartContainer>
                )}
            </CardContent>
        </Card>
    );
}
