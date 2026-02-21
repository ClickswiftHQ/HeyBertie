import type { LucideIcon } from 'lucide-react';
import { Card, CardContent } from '@/components/ui/card';

type StatCardProps = {
    label: string;
    value: string | number;
    icon: LucideIcon;
    subtitle?: string;
};

export function StatCard({ label, value, icon: Icon, subtitle }: StatCardProps) {
    return (
        <Card className="gap-0 py-4">
            <CardContent className="flex items-center gap-4">
                <div className="bg-muted flex size-10 shrink-0 items-center justify-center rounded-lg">
                    <Icon className="text-muted-foreground size-5" />
                </div>
                <div className="min-w-0 flex-1">
                    <p className="text-muted-foreground text-sm">{label}</p>
                    <p className="truncate text-2xl font-semibold tracking-tight">
                        {value}
                    </p>
                    {subtitle && (
                        <p className="text-muted-foreground text-xs">{subtitle}</p>
                    )}
                </div>
            </CardContent>
        </Card>
    );
}
