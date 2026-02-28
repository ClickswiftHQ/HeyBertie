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
    service: { id: number; name: string } | null;
};

export type RecentActivityItem = {
    id: string;
    type: 'booking_created' | 'booking_cancelled' | 'customer_created' | 'review_received';
    description: string;
    datetime: string;
};

export type ServiceItem = {
    id: number;
    name: string;
    description: string | null;
    duration_minutes: number;
    price: string | null;
    price_type: string;
    formatted_price: string;
    display_order: number;
    is_active: boolean;
    is_featured: boolean;
    bookings_count: number;
};

export type BookingDetail = {
    id: number;
    booking_reference: string;
    appointment_datetime: string;
    time: string;
    duration_minutes: number;
    status: string;
    price: string;
    pet_name: string | null;
    pet_breed: string | null;
    pet_size: string | null;
    customer_notes: string | null;
    pro_notes: string | null;
    can_be_cancelled: boolean;
    customer: { name: string; email: string | null; phone: string | null } | null;
    service: { name: string } | null;
    staff_member: { name: string } | null;
};

export type BookingGroup = {
    date: string;
    formatted_date: string;
    bookings: BookingDetail[];
};

export type BookingFilters = {
    status: string;
    from: string;
    to: string;
};

export type BookingStatusCounts = {
    all: number;
    pending: number;
    confirmed: number;
    completed: number;
};

export type ManualBookingLocation = {
    id: number;
    name: string;
    slug: string;
};

export type ManualBookingService = {
    id: number;
    name: string;
    duration_minutes: number;
    price: string | null;
    formatted_price: string;
    location_id: number | null;
};

export type ManualBookingCustomer = {
    id: number;
    name: string;
    email: string | null;
    phone: string | null;
};

// Customers page
export type CustomerListItem = {
    id: number;
    name: string;
    email: string | null;
    phone: string | null;
    total_bookings: number;
    total_spent: string;
    last_visit: string | null;
    is_active: boolean;
};

export type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

export type PaginatedData<T> = {
    data: T[];
    links: PaginationLink[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
};

export type CustomerFilters = {
    search: string;
    status: string;
};

// Analytics page
export type AnalyticsPeriodStats = {
    totalRevenue: number;
    totalBookings: number;
    newCustomers: number;
    noShowRate: number;
};

export type ChartDataPoint = {
    label: string;
    value: number;
};

export type TopServiceItem = {
    name: string;
    bookings_count: number;
    revenue: number;
};

export type BusiestDayItem = {
    day: string;
    bookings_count: number;
};

export type AvailabilityBlockItem = {
    id: number;
    day_of_week: number | null;
    start_time: string;
    end_time: string;
    specific_date: string | null;
    block_type: string;
    repeat_weekly: boolean;
    notes: string | null;
};
