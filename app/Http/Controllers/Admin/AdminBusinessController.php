<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminCancelBookingRequest;
use App\Http\Requests\Admin\AdminSuspendBusinessRequest;
use App\Http\Requests\Admin\AdminUpdateHandleRequest;
use App\Http\Requests\Admin\AdminUpdateSettingsRequest;
use App\Http\Requests\Admin\AdminUpdateSubscriptionRequest;
use App\Http\Requests\Admin\AdminUpdateTrialRequest;
use App\Http\Requests\Admin\AdminVerifyBusinessRequest;
use App\Models\Booking;
use App\Models\Business;
use App\Models\HandleChange;
use App\Models\SubscriptionStatus;
use App\Models\SubscriptionTier;
use App\Services\ActivityTimelineService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminBusinessController extends Controller
{
    public function __construct(private ActivityTimelineService $timelineService) {}

    public function index(Request $request): Response
    {
        $query = Business::query()
            ->with(['owner:id,name,email', 'subscriptionTier:id,slug,name', 'subscriptionStatus:id,slug,name'])
            ->withCount(['bookings', 'customers']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('handle', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhereHas('owner', fn ($q) => $q->where('email', 'like', "%{$search}%"));
            });
        }

        if ($verification = $request->input('verification')) {
            $query->where('verification_status', $verification);
        }

        if ($tier = $request->input('tier')) {
            $query->whereHas('subscriptionTier', fn ($q) => $q->where('slug', $tier));
        }

        if ($request->has('active')) {
            $query->where('is_active', $request->boolean('active'));
        }

        if ($request->has('onboarding')) {
            $query->where('onboarding_completed', $request->boolean('onboarding'));
        }

        $businesses = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        return Inertia::render('admin/businesses/index', [
            'businesses' => $businesses,
            'filters' => $request->only(['search', 'verification', 'tier', 'active', 'onboarding']),
            'tiers' => SubscriptionTier::orderBy('sort_order')->get(['id', 'slug', 'name']),
        ]);
    }

    public function show(Business $business): Response
    {
        $business->load([
            'owner:id,name,email,created_at',
            'subscriptionTier',
            'subscriptionStatus',
            'verificationDocuments.reviewedBy:id,name',
            'locations',
            'services',
            'staffMembers',
        ]);

        $recentBookings = $business->bookings()
            ->with(['customer:id,name,email', 'service:id,name'])
            ->orderByDesc('appointment_datetime')
            ->limit(20)
            ->get();

        $stats = [
            'totalBookings' => $business->bookings()->count(),
            'totalCustomers' => $business->customers()->count(),
            'totalRevenue' => (float) $business->transactions()->completed()->sum('amount'),
            'pageViews7d' => $business->pageViews()
                ->where('viewed_at', '>=', now()->subDays(7))
                ->count(),
        ];

        return Inertia::render('admin/businesses/show', [
            'business' => $business,
            'recentBookings' => $recentBookings,
            'stats' => $stats,
            'timeline' => $this->timelineService->forBusiness($business),
            'tiers' => SubscriptionTier::orderBy('sort_order')->get(['id', 'slug', 'name']),
            'statuses' => SubscriptionStatus::orderBy('id')->get(['id', 'slug', 'name']),
        ]);
    }

    public function verify(AdminVerifyBusinessRequest $request, Business $business): RedirectResponse
    {
        $isApproved = $request->validated('decision') === 'approved';

        $business->update([
            'verification_status' => $isApproved ? 'verified' : 'rejected',
            'verification_notes' => $request->validated('notes'),
            'verified_at' => $isApproved ? now() : null,
        ]);

        $business->verificationDocuments()->pending()->update([
            'status' => $isApproved ? 'approved' : 'rejected',
            'reviewer_notes' => $request->validated('notes'),
            'reviewed_by_user_id' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        $status = $isApproved ? 'approved' : 'rejected';

        return back()->with('success', "Business verification {$status}.");
    }

    public function suspend(AdminSuspendBusinessRequest $request, Business $business): RedirectResponse
    {
        $isSuspending = $request->validated('action') === 'suspend';

        $business->update([
            'is_active' => ! $isSuspending,
        ]);

        if ($request->validated('reason')) {
            $business->update(['verification_notes' => $request->validated('reason')]);
        }

        $action = $isSuspending ? 'suspended' : 'reactivated';

        return back()->with('success', "Business {$action}.");
    }

    public function updateSubscription(AdminUpdateSubscriptionRequest $request, Business $business): RedirectResponse
    {
        $business->update([
            'subscription_tier_id' => $request->validated('subscription_tier_id'),
            'subscription_status_id' => $request->validated('subscription_status_id'),
        ]);

        return back()->with('success', 'Subscription updated.');
    }

    public function updateTrial(AdminUpdateTrialRequest $request, Business $business): RedirectResponse
    {
        $trialEndsAt = $request->validated('trial_ends_at')
            ? Carbon::parse($request->validated('trial_ends_at'))
            : null;

        $business->update(['trial_ends_at' => $trialEndsAt]);

        if ($trialEndsAt) {
            $trialStatus = SubscriptionStatus::where('slug', 'trial')->first();
            if ($trialStatus) {
                $business->update(['subscription_status_id' => $trialStatus->id]);
            }
        }

        return back()->with('success', 'Trial period updated.');
    }

    public function updateHandle(AdminUpdateHandleRequest $request, Business $business): RedirectResponse
    {
        $oldHandle = $business->handle;
        $newHandle = $request->validated('handle');

        if ($oldHandle === $newHandle) {
            return back();
        }

        HandleChange::create([
            'business_id' => $business->id,
            'old_handle' => $oldHandle,
            'new_handle' => $newHandle,
            'changed_by_user_id' => $request->user()->id,
            'changed_at' => now(),
        ]);

        $business->update(['handle' => $newHandle]);

        return redirect()->route('admin.businesses.show', $business)
            ->with('success', "Handle changed from @{$oldHandle} to @{$newHandle}.");
    }

    public function updateSettings(AdminUpdateSettingsRequest $request, Business $business): RedirectResponse
    {
        $currentSettings = $business->settings ?? [];
        $business->update([
            'settings' => array_merge($currentSettings, $request->validated()),
        ]);

        return back()->with('success', 'Business settings updated.');
    }

    public function cancelBooking(AdminCancelBookingRequest $request, Business $business, Booking $booking): RedirectResponse
    {
        if ($booking->business_id !== $business->id) {
            abort(404);
        }

        if (in_array($booking->status, ['cancelled', 'completed', 'no_show'])) {
            return back()->with('error', 'This booking cannot be cancelled.');
        }

        $booking->cancel($request->user(), $request->validated('cancellation_reason'));

        return back()->with('success', "Booking {$booking->booking_reference} cancelled.");
    }
}
