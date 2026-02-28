export type BusinessSummary = {
    id: number;
    name: string;
    handle: string;
    logo_url: string | null;
};

export type CurrentBusiness = BusinessSummary & {
    subscription_tier: string;
    has_active_subscription: boolean;
    on_trial: boolean;
    trial_days_remaining: number | null;
};

export type OverviewStats = {
    todaysBookings: number;
    weeklyRevenue: number;
    totalCustomers: number;
    pageViews: number;
    pendingBookings: number;
    averageRating: number | null;
    noShowRate: number;
    monthlyBookings: number;
};

export type UpcomingBooking = {
    id: number;
    appointment_datetime: string;
    duration_minutes: number;
    status: string;
    customer: { id: number; name: string };
    service: { id: number; name: string };
};

export type RecentActivityItem = {
    id: string;
    type: 'booking_created' | 'booking_cancelled' | 'customer_created' | 'review_received';
    description: string;
    datetime: string;
};
