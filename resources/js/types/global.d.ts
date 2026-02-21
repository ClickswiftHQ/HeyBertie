import type { Auth } from '@/types/auth';
import type { BusinessSummary, CurrentBusiness } from '@/types/business';

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            sidebarOpen: boolean;
            currentBusiness: CurrentBusiness | null;
            userBusinesses: BusinessSummary[];
            [key: string]: unknown;
        };
    }
}
