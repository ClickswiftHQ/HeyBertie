<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminResetPasswordRequest;
use App\Models\Customer;
use App\Models\EmailLog;
use App\Models\SmsLog;
use App\Models\User;
use App\Services\ActivityTimelineService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class AdminUserController extends Controller
{
    public function __construct(private ActivityTimelineService $timelineService) {}

    public function index(Request $request): Response
    {
        $query = User::query()
            ->withCount(['ownedBusinesses', 'pets']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('registered')) {
            $query->where('is_registered', $request->input('registered') === '1');
        }

        if ($request->filled('super')) {
            $query->where('super', $request->input('super') === '1');
        }

        if ($request->input('has_businesses') === '1') {
            $query->whereHas('ownedBusinesses');
        } elseif ($request->input('has_businesses') === '0') {
            $query->whereDoesntHave('ownedBusinesses');
        }

        if ($role = $request->input('role')) {
            $query->where('role', $role);
        }

        $users = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        return Inertia::render('admin/users/index', [
            'users' => $users,
            'filters' => $request->only(['search', 'registered', 'super', 'has_businesses', 'role']),
        ]);
    }

    public function show(User $user): Response
    {
        $user->load([
            'ownedBusinesses.subscriptionTier',
            'ownedBusinesses.subscriptionStatus',
            'businesses',
            'pets.species',
            'pets.breed',
            'pets.sizeCategory',
        ]);

        // Bookings as customer across all businesses
        $customerIds = Customer::where('user_id', $user->id)->pluck('id');
        $recentBookings = \App\Models\Booking::whereIn('customer_id', $customerIds)
            ->with(['customer:id,name,email', 'service:id,name', 'business:id,name,handle'])
            ->orderByDesc('created_at')
            ->limit(20)
            ->get()
            ->map(fn ($b) => [
                'id' => $b->id,
                'booking_reference' => $b->booking_reference,
                'appointment_datetime' => $b->appointment_datetime?->toIso8601String(),
                'duration_minutes' => $b->duration_minutes,
                'status' => $b->status,
                'price' => $b->price,
                'business' => $b->business ? ['id' => $b->business->id, 'name' => $b->business->name, 'handle' => $b->business->handle] : null,
                'service' => $b->service ? ['id' => $b->service->id, 'name' => $b->service->name] : null,
            ]);

        // Communication history
        $communications = $this->getCommunicationHistory($user);

        // Staff memberships
        $staffMemberships = $user->businesses->map(fn ($business) => [
            'id' => $business->id,
            'name' => $business->name,
            'handle' => $business->handle,
            'role_id' => $business->pivot->business_role_id,
            'is_active' => (bool) $business->pivot->is_active,
            'accepted_at' => $business->pivot->accepted_at,
        ]);

        return Inertia::render('admin/users/show', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'super' => $user->super,
                'is_registered' => $user->is_registered,
                'email_verified_at' => $user->email_verified_at?->toIso8601String(),
                'two_factor_enabled' => $user->two_factor_confirmed_at !== null,
                'last_login' => $user->last_login,
                'created_at' => $user->created_at->toIso8601String(),
            ],
            'ownedBusinesses' => $user->ownedBusinesses->map(fn ($b) => [
                'id' => $b->id,
                'name' => $b->name,
                'handle' => $b->handle,
                'verification_status' => $b->verification_status,
                'is_active' => $b->is_active,
                'subscription_tier' => $b->subscriptionTier ? ['slug' => $b->subscriptionTier->slug, 'name' => $b->subscriptionTier->name] : null,
                'subscription_status' => $b->subscriptionStatus ? ['slug' => $b->subscriptionStatus->slug, 'name' => $b->subscriptionStatus->name] : null,
            ]),
            'staffMemberships' => $staffMemberships,
            'pets' => $user->pets->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'species' => $p->species?->name,
                'breed' => $p->breed?->name,
                'size' => $p->sizeCategory?->name,
                'is_active' => $p->is_active,
            ]),
            'recentBookings' => $recentBookings,
            'communications' => $communications,
            'timeline' => $this->timelineService->forUser($user),
        ]);
    }

    public function impersonate(Request $request, User $user): RedirectResponse
    {
        if ($user->super) {
            return back()->with('error', 'Cannot impersonate another super admin.');
        }

        if ($user->id === $request->user()->id) {
            return back()->with('error', 'Cannot impersonate yourself.');
        }

        $request->session()->put('impersonating_from', $request->user()->id);
        $request->session()->put('impersonating_from_name', $request->user()->name);

        Auth::login($user);

        return redirect('/');
    }

    public function leaveImpersonation(Request $request): RedirectResponse
    {
        $originalUserId = $request->session()->pull('impersonating_from');
        $request->session()->forget('impersonating_from_name');

        if (! $originalUserId) {
            return redirect('/');
        }

        $originalUser = User::find($originalUserId);
        if ($originalUser) {
            Auth::login($originalUser);
        }

        return redirect('/admin/users');
    }

    public function resetPassword(AdminResetPasswordRequest $request, User $user): RedirectResponse
    {
        $user->update([
            'password' => Hash::make($request->validated('password')),
        ]);

        return back()->with('success', 'Password has been reset.');
    }

    /**
     * @return list<array{type: string, subject: string, status: string, booking_reference: string|null, business_name: string|null, timestamp: string}>
     */
    private function getCommunicationHistory(User $user): array
    {
        $communications = collect();

        // Emails sent to user's email
        EmailLog::where('to_email', $user->email)
            ->with('booking:id,booking_reference', 'business:id,name')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->each(function ($log) use ($communications) {
                $communications->push([
                    'type' => 'email',
                    'subject' => $log->subject,
                    'status' => $log->status,
                    'booking_reference' => $log->booking?->booking_reference,
                    'business_name' => $log->business?->name,
                    'timestamp' => $log->created_at->toIso8601String(),
                ]);
            });

        // SMS sent to user's phone (via customer records)
        $phones = Customer::where('user_id', $user->id)
            ->whereNotNull('phone')
            ->pluck('phone')
            ->unique();

        if ($phones->isNotEmpty()) {
            SmsLog::whereIn('phone_number', $phones)
                ->with('booking:id,booking_reference', 'business:id,name')
                ->orderByDesc('created_at')
                ->limit(50)
                ->get()
                ->each(function ($log) use ($communications) {
                    $communications->push([
                        'type' => 'sms',
                        'subject' => $log->message_type,
                        'status' => $log->status,
                        'booking_reference' => $log->booking?->booking_reference,
                        'business_name' => $log->business?->name,
                        'timestamp' => $log->created_at->toIso8601String(),
                    ]);
                });
        }

        return $communications->sortByDesc('timestamp')->values()->all();
    }
}
