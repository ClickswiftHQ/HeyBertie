import { Link, usePage } from '@inertiajs/react';
import { ChevronsUpDown, Store } from 'lucide-react';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    useSidebar,
} from '@/components/ui/sidebar';
import { useIsMobile } from '@/hooks/use-mobile';

export function BusinessSwitcher() {
    const { currentBusiness, userBusinesses } = usePage().props;
    const { state } = useSidebar();
    const isMobile = useIsMobile();

    if (!currentBusiness) {
        return null;
    }

    const hasMultipleBusinesses = userBusinesses.length > 1;

    if (!hasMultipleBusinesses) {
        return (
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" className="cursor-default">
                        <div className="flex aspect-square size-8 items-center justify-center rounded-md bg-sidebar-primary text-sidebar-primary-foreground">
                            {currentBusiness.logo_url ? (
                                <img
                                    src={currentBusiness.logo_url}
                                    alt={currentBusiness.name}
                                    className="size-5 rounded-sm object-cover"
                                />
                            ) : (
                                <Store className="size-4" />
                            )}
                        </div>
                        <div className="ml-1 grid flex-1 text-left text-sm">
                            <span className="truncate leading-tight font-semibold">
                                {currentBusiness.name}
                            </span>
                        </div>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        );
    }

    return (
        <SidebarMenu>
            <SidebarMenuItem>
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <SidebarMenuButton size="lg">
                            <div className="flex aspect-square size-8 items-center justify-center rounded-md bg-sidebar-primary text-sidebar-primary-foreground">
                                {currentBusiness.logo_url ? (
                                    <img
                                        src={currentBusiness.logo_url}
                                        alt={currentBusiness.name}
                                        className="size-5 rounded-sm object-cover"
                                    />
                                ) : (
                                    <Store className="size-4" />
                                )}
                            </div>
                            <div className="ml-1 grid flex-1 text-left text-sm">
                                <span className="truncate leading-tight font-semibold">
                                    {currentBusiness.name}
                                </span>
                            </div>
                            <ChevronsUpDown className="ml-auto size-4" />
                        </SidebarMenuButton>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent
                        className="w-(--radix-dropdown-menu-trigger-width) min-w-56 rounded-lg"
                        align="start"
                        side={
                            isMobile
                                ? 'bottom'
                                : state === 'collapsed'
                                    ? 'right'
                                    : 'bottom'
                        }
                    >
                        {userBusinesses.map((business) => (
                            <DropdownMenuItem key={business.id} asChild>
                                <Link
                                    href={`/${business.handle}/dashboard`}
                                    className="flex items-center gap-2"
                                    prefetch
                                >
                                    <div className="flex size-6 items-center justify-center rounded-sm border">
                                        {business.logo_url ? (
                                            <img
                                                src={business.logo_url}
                                                alt={business.name}
                                                className="size-4 rounded-sm object-cover"
                                            />
                                        ) : (
                                            <Store className="size-3" />
                                        )}
                                    </div>
                                    <span>{business.name}</span>
                                </Link>
                            </DropdownMenuItem>
                        ))}
                    </DropdownMenuContent>
                </DropdownMenu>
            </SidebarMenuItem>
        </SidebarMenu>
    );
}
