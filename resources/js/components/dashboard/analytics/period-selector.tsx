import { router, usePage } from '@inertiajs/react';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

type PeriodSelectorProps = {
    period: string;
};

export function PeriodSelector({ period }: PeriodSelectorProps) {
    const handle = usePage().props.currentBusiness!.handle;

    function onChange(value: string) {
        router.get(
            `/${handle}/analytics`,
            { period: value === '30' ? undefined : value },
            { preserveState: true, preserveScroll: true },
        );
    }

    return (
        <Select value={period} onValueChange={onChange}>
            <SelectTrigger className="w-[160px]">
                <SelectValue />
            </SelectTrigger>
            <SelectContent>
                <SelectItem value="7">Last 7 days</SelectItem>
                <SelectItem value="30">Last 30 days</SelectItem>
                <SelectItem value="90">Last 90 days</SelectItem>
                <SelectItem value="all">All time</SelectItem>
            </SelectContent>
        </Select>
    );
}
