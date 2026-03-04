export type AdminOverviewStats = {
    totalBusinesses: number;
    verifiedBusinesses: number;
    pendingVerifications: number;
    totalUsers: number;
    registeredUsers: number;
    totalBookings: number;
    todaysBookings: number;
    weeklyBookings: number;
    monthlyBookings: number;
    mrr: number;
    subscriptionBreakdown: Record<string, Record<string, number>>;
    expiringTrials: AdminExpiringTrial[];
    recentSignups: AdminRecentSignup[];
};

export type AdminExpiringTrial = {
    id: number;
    name: string;
    handle: string;
    trial_ends_at: string;
    owner_name: string;
};

export type AdminRecentSignup = {
    id: number;
    name: string;
    handle: string;
    owner_name: string;
    tier: string;
    verification_status: string;
    created_at: string;
};

export type AdminBusinessListItem = {
    id: number;
    name: string;
    handle: string;
    email: string | null;
    verification_status: string;
    is_active: boolean;
    onboarding_completed: boolean;
    created_at: string;
    bookings_count: number;
    customers_count: number;
    owner: { id: number; name: string; email: string } | null;
    subscription_tier: { id: number; slug: string; name: string } | null;
    subscription_status: { id: number; slug: string; name: string } | null;
};

export type AdminBusinessDetail = {
    id: number;
    name: string;
    handle: string;
    slug: string;
    description: string | null;
    email: string | null;
    phone: string | null;
    website: string | null;
    logo_url: string | null;
    verification_status: string;
    verification_notes: string | null;
    verified_at: string | null;
    is_active: boolean;
    onboarding_completed: boolean;
    trial_ends_at: string | null;
    stripe_id: string | null;
    stripe_connect_id: string | null;
    stripe_connect_onboarding_complete: boolean;
    settings: Record<string, unknown> | null;
    created_at: string;
    owner: { id: number; name: string; email: string; created_at: string };
    subscription_tier: { id: number; slug: string; name: string; monthly_price_pence: number };
    subscription_status: { id: number; slug: string; name: string };
    verification_documents: AdminVerificationDocument[];
    locations: AdminLocation[];
    services: AdminService[];
    staff_members: AdminStaffMember[];
};

export type AdminVerificationDocument = {
    id: number;
    document_type: string;
    file_path: string;
    original_filename: string;
    status: string;
    reviewer_notes: string | null;
    reviewed_at: string | null;
    reviewed_by: { id: number; name: string } | null;
    created_at: string;
};

export type AdminLocation = {
    id: number;
    name: string;
    city: string | null;
    town: string | null;
    postcode: string | null;
    is_mobile: boolean;
    is_active: boolean;
    accepts_bookings: boolean;
};

export type AdminService = {
    id: number;
    name: string;
    duration_minutes: number;
    price: string | null;
    is_active: boolean;
};

export type AdminStaffMember = {
    id: number;
    display_name: string;
    role: string | null;
    is_active: boolean;
    commission_rate: string | null;
};

export type AdminBookingListItem = {
    id: number;
    booking_reference: string;
    appointment_datetime: string;
    duration_minutes: number;
    status: string;
    price: string;
    customer: { id: number; name: string; email: string | null } | null;
    service: { id: number; name: string } | null;
};

export type AdminBusinessStats = {
    totalBookings: number;
    totalCustomers: number;
    totalRevenue: number;
    pageViews7d: number;
};

export type AdminTimelineEvent = {
    type: string;
    description: string;
    timestamp: string;
    metadata: Record<string, unknown>;
};

export type AdminTier = {
    id: number;
    slug: string;
    name: string;
};

export type AdminStatus = {
    id: number;
    slug: string;
    name: string;
};

export type AdminBusinessFilters = {
    search?: string;
    verification?: string;
    tier?: string;
    active?: string;
    onboarding?: string;
};

export type AdminUserListItem = {
    id: number;
    name: string;
    email: string;
    role: string;
    super: boolean;
    is_registered: boolean;
    created_at: string;
    owned_businesses_count: number;
    pets_count: number;
};

export type AdminUserDetail = {
    id: number;
    name: string;
    email: string;
    role: string;
    super: boolean;
    is_registered: boolean;
    email_verified_at: string | null;
    two_factor_enabled: boolean;
    last_login: string | null;
    created_at: string;
};

export type AdminUserBusiness = {
    id: number;
    name: string;
    handle: string;
    verification_status: string;
    is_active: boolean;
    subscription_tier: { slug: string; name: string } | null;
    subscription_status: { slug: string; name: string } | null;
};

export type AdminUserStaffMembership = {
    id: number;
    name: string;
    handle: string;
    role_id: number | null;
    is_active: boolean;
    accepted_at: string | null;
};

export type AdminUserPet = {
    id: number;
    name: string;
    species: string | null;
    breed: string | null;
    size: string | null;
    is_active: boolean;
};

export type AdminUserBooking = {
    id: number;
    booking_reference: string;
    appointment_datetime: string | null;
    duration_minutes: number;
    status: string;
    price: string;
    business: { id: number; name: string; handle: string } | null;
    service: { id: number; name: string } | null;
};

export type AdminCommunicationEntry = {
    type: 'email' | 'sms';
    subject: string;
    status: string;
    booking_reference: string | null;
    business_name: string | null;
    timestamp: string;
};

export type AdminUserFilters = {
    search?: string;
    registered?: string;
    super?: string;
    has_businesses?: string;
    role?: string;
};
