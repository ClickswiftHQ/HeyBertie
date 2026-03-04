import { Mail, MessageSquare } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import type { AdminCommunicationEntry } from '@/types';

function StatusBadge({ status }: { status: string }) {
    if (status === 'delivered' || status === 'sent') {
        return (
            <Badge className="border-0 bg-emerald-100 text-emerald-700">
                {status}
            </Badge>
        );
    }
    if (status === 'failed' || status === 'bounced') {
        return <Badge variant="destructive">{status}</Badge>;
    }
    if (status === 'opened' || status === 'clicked') {
        return (
            <Badge className="border-0 bg-blue-100 text-blue-700">
                {status}
            </Badge>
        );
    }
    return <Badge variant="outline">{status}</Badge>;
}

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

export function CommunicationLog({
    entries,
}: {
    entries: AdminCommunicationEntry[];
}) {
    if (entries.length === 0) {
        return (
            <p className="text-muted-foreground text-sm">
                No communications found.
            </p>
        );
    }

    return (
        <div className="space-y-3">
            {entries.map((entry, i) => (
                <div
                    key={i}
                    className="flex items-start gap-3 rounded-lg border p-3"
                >
                    <div className="mt-0.5">
                        {entry.type === 'email' ? (
                            <Mail className="text-muted-foreground size-4" />
                        ) : (
                            <MessageSquare className="text-muted-foreground size-4" />
                        )}
                    </div>
                    <div className="min-w-0 flex-1">
                        <div className="flex items-center gap-2">
                            <span className="truncate text-sm font-medium">
                                {entry.subject}
                            </span>
                            <StatusBadge status={entry.status} />
                        </div>
                        <div className="text-muted-foreground mt-1 flex flex-wrap gap-x-3 text-xs">
                            {entry.business_name && (
                                <span>{entry.business_name}</span>
                            )}
                            {entry.booking_reference && (
                                <span>Ref: {entry.booking_reference}</span>
                            )}
                            <span>{formatTimestamp(entry.timestamp)}</span>
                        </div>
                    </div>
                </div>
            ))}
        </div>
    );
}
