import { usePage } from '@inertiajs/react';
import {
    Calendar,
    Clock,
    LayoutGrid,
    ListChecks,
    TrendingUp,
    Users,
} from 'lucide-react';
import { BusinessSwitcher } from '@/components/business-switcher';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
} from '@/components/ui/sidebar';
import type { NavItem } from '@/types';

export function AppSidebar() {
    const { currentBusiness } = usePage().props;
    const handle = currentBusiness?.handle;

    const mainNavItems: NavItem[] = handle
        ? [
              {
                  title: 'Overview',
                  href: `/${handle}/dashboard`,
                  icon: LayoutGrid,
              },
              {
                  title: 'Services',
                  href: `/${handle}/services`,
                  icon: ListChecks,
              },
              {
                  title: 'Calendar',
                  href: `/${handle}/calendar`,
                  icon: Calendar,
              },
              {
                  title: 'Customers',
                  href: `/${handle}/customers`,
                  icon: Users,
              },
              {
                  title: 'Availability',
                  href: `/${handle}/availability`,
                  icon: Clock,
              },
              {
                  title: 'Analytics',
                  href: `/${handle}/analytics`,
                  icon: TrendingUp,
              },
          ]
        : [];

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <BusinessSwitcher />
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} label="Business" />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
