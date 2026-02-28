import { Link } from '@inertiajs/react';
import { cn } from '@/lib/utils';
import type { PaginationLink } from '@/types';

type PaginationProps = {
    links: PaginationLink[];
};

export function Pagination({ links }: PaginationProps) {
    if (links.length <= 3) {
        return null;
    }

    return (
        <nav className="flex items-center justify-center gap-1">
            {links.map((link, index) => {
                if (!link.url) {
                    return (
                        <span
                            key={index}
                            className="text-muted-foreground inline-flex h-9 items-center justify-center px-3 text-sm"
                            dangerouslySetInnerHTML={{ __html: link.label }}
                        />
                    );
                }

                return (
                    <Link
                        key={index}
                        href={link.url}
                        preserveState
                        preserveScroll
                        className={cn(
                            'inline-flex h-9 items-center justify-center rounded-md px-3 text-sm transition-colors',
                            link.active
                                ? 'bg-primary text-primary-foreground'
                                : 'hover:bg-muted',
                        )}
                        dangerouslySetInnerHTML={{ __html: link.label }}
                    />
                );
            })}
        </nav>
    );
}
