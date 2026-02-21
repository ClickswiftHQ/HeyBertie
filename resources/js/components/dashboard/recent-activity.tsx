import {
    CalendarPlus,
    CalendarX,
    Star,
    UserPlus,
} from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { RecentActivityItem } from '@/types';

type RecentActivityProps = {
    activities: RecentActivityItem[];
};

function activityIcon(type: RecentActivityItem['type']) {
    switch (type) {
        case 'booking_created':
            return <CalendarPlus className="size-4 text-green-600" />;
        case 'booking_cancelled':
            return <CalendarX className="size-4 text-red-500" />;
        case 'customer_created':
            return <UserPlus className="size-4 text-blue-500" />;
        case 'review_received':
            return <Star className="size-4 text-yellow-500" />;
    }
}

function timeAgo(datetime: string): string {
    const now = new Date();
    const date = new Date(datetime);
    const diffMs = now.getTime() - date.getTime();
    const diffMinutes = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);

    if (diffMinutes < 1) {
        return 'just now';
    }

    if (diffMinutes < 60) {
        return `${diffMinutes}m ago`;
    }

    if (diffHours < 24) {
        return `${diffHours}h ago`;
    }

    if (diffDays === 1) {
        return 'yesterday';
    }

    return `${diffDays}d ago`;
}

export function RecentActivity({ activities }: RecentActivityProps) {
    return (
        <Card>
            <CardHeader>
                <CardTitle>Recent Activity</CardTitle>
            </CardHeader>
            <CardContent>
                {activities.length === 0 ? (
                    <p className="text-muted-foreground text-sm">
                        No recent activity
                    </p>
                ) : (
                    <div className="space-y-3">
                        {activities.map((activity) => (
                            <div
                                key={activity.id}
                                className="flex items-start gap-3"
                            >
                                <div className="mt-0.5 shrink-0">
                                    {activityIcon(activity.type)}
                                </div>
                                <div className="min-w-0 flex-1">
                                    <p className="text-sm">
                                        {activity.description}
                                    </p>
                                    <p className="text-muted-foreground text-xs">
                                        {timeAgo(activity.datetime)}
                                    </p>
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </CardContent>
        </Card>
    );
}
