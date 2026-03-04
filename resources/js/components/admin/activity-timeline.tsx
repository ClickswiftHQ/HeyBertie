import {
    Ban,
    BookOpen,
    Building2,
    Calendar,
    CheckCircle,
    CreditCard,
    FileText,
    LogIn,
    Mail,
    RefreshCw,
    ShieldCheck,
    UserPlus,
    XCircle,
} from 'lucide-react';
import type { AdminTimelineEvent } from '@/types';

const iconMap: Record<string, typeof Building2> = {
    business_created: Building2,
    onboarding_completed: CheckCircle,
    business_verified: ShieldCheck,
    business_rejected: XCircle,
    stripe_connect: CreditCard,
    handle_changed: RefreshCw,
    document_uploaded: FileText,
    document_approved: CheckCircle,
    document_rejected: XCircle,
    booking_created: Calendar,
    booking_cancelled: Ban,
    booking_completed: CheckCircle,
    account_created: UserPlus,
    email_verified: Mail,
    staff_invite_accepted: UserPlus,
    last_login: LogIn,
};

function formatTimestamp(iso: string): string {
    const date = new Date(iso);
    return date.toLocaleDateString('en-GB', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

export function ActivityTimeline({
    events,
}: {
    events: AdminTimelineEvent[];
}) {
    if (events.length === 0) {
        return (
            <p className="text-muted-foreground text-sm">No activity yet.</p>
        );
    }

    return (
        <div className="relative space-y-0">
            {/* Vertical line */}
            <div className="bg-border absolute top-0 bottom-0 left-4 w-px" />

            {events.map((event, i) => {
                const Icon = iconMap[event.type] ?? BookOpen;

                return (
                    <div key={i} className="relative flex gap-4 pb-4">
                        <div className="bg-background border-border relative z-10 flex size-8 shrink-0 items-center justify-center rounded-full border">
                            <Icon className="text-muted-foreground size-3.5" />
                        </div>
                        <div className="min-w-0 flex-1 pt-0.5">
                            <p className="text-sm">{event.description}</p>
                            <p className="text-muted-foreground text-xs">
                                {formatTimestamp(event.timestamp)}
                            </p>
                        </div>
                    </div>
                );
            })}
        </div>
    );
}
