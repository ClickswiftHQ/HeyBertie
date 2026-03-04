<?php

namespace App\Services;

use App\Models\Business;
use App\Models\Customer;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ActivityTimelineService
{
    /**
     * @return list<array{type: string, description: string, timestamp: string, metadata: array<string, mixed>}>
     */
    public function forBusiness(Business $business, int $limit = 50): array
    {
        $events = collect();

        $events->push($this->event('business_created', 'Business created', $business->created_at));

        if ($business->onboarding_completed) {
            $events->push($this->event('onboarding_completed', 'Onboarding completed', $business->updated_at));
        }

        if ($business->verified_at) {
            $events->push($this->event('business_verified', 'Business verified', $business->verified_at));
        } elseif ($business->verification_status === 'rejected') {
            $events->push($this->event(
                'business_rejected',
                'Verification rejected'.($business->verification_notes ? ': '.$business->verification_notes : ''),
                $business->updated_at,
            ));
        }

        if ($business->stripe_connect_id) {
            $events->push($this->event(
                'stripe_connect',
                'Stripe Connect account set up',
                $business->updated_at,
                ['stripe_connect_id' => $business->stripe_connect_id],
            ));
        }

        $this->addHandleChangeEvents($business, $events);
        $this->addVerificationDocumentEvents($business, $events);
        $this->addBookingEvents($business, $events, $limit);

        return $events
            ->sortByDesc('timestamp')
            ->take($limit)
            ->values()
            ->all();
    }

    /**
     * @return list<array{type: string, description: string, timestamp: string, metadata: array<string, mixed>}>
     */
    public function forUser(User $user, int $limit = 50): array
    {
        $events = collect();

        $events->push($this->event('account_created', 'Account created', $user->created_at));

        if ($user->email_verified_at) {
            $events->push($this->event('email_verified', 'Email verified', $user->email_verified_at));
        }

        // Businesses created
        $user->ownedBusinesses()->select(['id', 'name', 'handle', 'created_at', 'owner_user_id'])->get()
            ->each(function ($business) use ($events) {
                $events->push($this->event(
                    'business_created',
                    "Created business {$business->name}",
                    $business->created_at,
                    ['business_id' => $business->id, 'business_handle' => $business->handle],
                ));
            });

        // Staff invites accepted
        $user->businesses()->wherePivotNotNull('accepted_at')->get()
            ->each(function ($business) use ($events) {
                $events->push($this->event(
                    'staff_invite_accepted',
                    "Joined {$business->name} as staff",
                    $business->pivot->accepted_at,
                    ['business_id' => $business->id],
                ));
            });

        // Bookings via customer records
        $this->addUserBookingEvents($user, $events, $limit);

        if ($user->last_login) {
            $events->push($this->event('last_login', 'Last login', $user->last_login));
        }

        return $events
            ->sortByDesc('timestamp')
            ->take($limit)
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, array{type: string, description: string, timestamp: string, metadata: array<string, mixed>}>  $events
     */
    private function addUserBookingEvents(User $user, Collection $events, int $limit): void
    {
        $customerIds = Customer::where('user_id', $user->id)->pluck('id');

        if ($customerIds->isEmpty()) {
            return;
        }

        \App\Models\Booking::whereIn('customer_id', $customerIds)
            ->with('business:id,name')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->each(function ($booking) use ($events) {
                $businessName = $booking->business?->name ?? 'Unknown';
                $ref = $booking->booking_reference;

                $events->push($this->event(
                    'booking_created',
                    "Booked at {$businessName} (ref {$ref})",
                    $booking->created_at,
                    ['booking_id' => $booking->id, 'booking_reference' => $ref],
                ));

                if ($booking->status === 'cancelled' && $booking->cancelled_at) {
                    $events->push($this->event(
                        'booking_cancelled',
                        "Cancelled booking {$ref} at {$businessName}",
                        $booking->cancelled_at,
                        ['booking_id' => $booking->id, 'booking_reference' => $ref],
                    ));
                }
            });
    }

    /**
     * @param  Collection<int, array{type: string, description: string, timestamp: string, metadata: array<string, mixed>}>  $events
     */
    private function addHandleChangeEvents(Business $business, Collection $events): void
    {
        $business->handleChanges()
            ->orderByDesc('changed_at')
            ->limit(10)
            ->get()
            ->each(function ($change) use ($events) {
                $events->push($this->event(
                    'handle_changed',
                    "Handle changed from @{$change->old_handle} to @{$change->new_handle}",
                    $change->changed_at,
                    ['old_handle' => $change->old_handle, 'new_handle' => $change->new_handle],
                ));
            });
    }

    /**
     * @param  Collection<int, array{type: string, description: string, timestamp: string, metadata: array<string, mixed>}>  $events
     */
    private function addVerificationDocumentEvents(Business $business, Collection $events): void
    {
        $business->verificationDocuments()
            ->with('reviewedBy:id,name')
            ->orderByDesc('created_at')
            ->get()
            ->each(function ($doc) use ($events) {
                $events->push($this->event(
                    'document_uploaded',
                    ucfirst(str_replace('_', ' ', $doc->document_type)).' uploaded',
                    $doc->created_at,
                    ['document_type' => $doc->document_type],
                ));

                if ($doc->reviewed_at) {
                    $reviewer = $doc->reviewedBy?->name ?? 'Admin';
                    $events->push($this->event(
                        'document_'.$doc->status,
                        ucfirst(str_replace('_', ' ', $doc->document_type)).' '.$doc->status.' by '.$reviewer,
                        $doc->reviewed_at,
                        ['document_type' => $doc->document_type, 'reviewer' => $reviewer],
                    ));
                }
            });
    }

    /**
     * @param  Collection<int, array{type: string, description: string, timestamp: string, metadata: array<string, mixed>}>  $events
     */
    private function addBookingEvents(Business $business, Collection $events, int $limit): void
    {
        $business->bookings()
            ->with('customer:id,name')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->each(function ($booking) use ($events) {
                $customerName = $booking->customer?->name ?? 'Unknown';
                $ref = $booking->booking_reference;

                $events->push($this->event(
                    'booking_created',
                    "Booking {$ref} created for {$customerName}",
                    $booking->created_at,
                    ['booking_id' => $booking->id, 'booking_reference' => $ref],
                ));

                if ($booking->status === 'cancelled' && $booking->cancelled_at) {
                    $events->push($this->event(
                        'booking_cancelled',
                        "Booking {$ref} cancelled".($booking->cancellation_reason ? ': '.$booking->cancellation_reason : ''),
                        $booking->cancelled_at,
                        ['booking_id' => $booking->id, 'booking_reference' => $ref],
                    ));
                }

                if ($booking->status === 'completed') {
                    $events->push($this->event(
                        'booking_completed',
                        "Booking {$ref} completed for {$customerName}",
                        $booking->updated_at,
                        ['booking_id' => $booking->id, 'booking_reference' => $ref],
                    ));
                }
            });
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @return array{type: string, description: string, timestamp: string, metadata: array<string, mixed>}
     */
    private function event(string $type, string $description, mixed $timestamp, array $metadata = []): array
    {
        return [
            'type' => $type,
            'description' => $description,
            'timestamp' => $timestamp instanceof \DateTimeInterface
                ? $timestamp->toIso8601String()
                : ($timestamp ? Carbon::parse($timestamp)->toIso8601String() : now()->toIso8601String()),
            'metadata' => $metadata,
        ];
    }
}
