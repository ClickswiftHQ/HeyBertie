import { usePage } from '@inertiajs/react';
import { AdminSidebar } from '@/components/admin/admin-sidebar';
import { ImpersonationBanner } from '@/components/admin/impersonation-banner';
import { AppContent } from '@/components/app-content';
import { AppShell } from '@/components/app-shell';
import { AppSidebarHeader } from '@/components/app-sidebar-header';
import type { AppLayoutProps } from '@/types';

export default function AdminSidebarLayout({
    children,
    breadcrumbs = [],
}: AppLayoutProps) {
    const { impersonating } = usePage<{
        impersonating: { from_id: number; from_name: string } | null;
    }>().props;

    return (
        <AppShell variant="sidebar">
            <AdminSidebar />
            <AppContent variant="sidebar" className="overflow-x-hidden">
                {impersonating && (
                    <ImpersonationBanner fromName={impersonating.from_name} />
                )}
                <AppSidebarHeader breadcrumbs={breadcrumbs} />
                {children}
            </AppContent>
        </AppShell>
    );
}
