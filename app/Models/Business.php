<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $name
 * @property string $handle
 * @property string $slug
 * @property string|null $description
 * @property string|null $logo_url
 * @property string|null $cover_image_url
 * @property string|null $phone
 * @property string|null $email
 * @property string|null $website
 * @property \Carbon\CarbonImmutable|null $trial_ends_at
 * @property string|null $stripe_customer_id
 * @property string|null $stripe_subscription_id
 * @property string $verification_status
 * @property string|null $verification_notes
 * @property \Carbon\CarbonImmutable|null $verified_at
 * @property int $owner_user_id
 * @property bool $is_active
 * @property array<array-key, mixed>|null $settings
 * @property array<array-key, mixed>|null $onboarding
 * @property bool $onboarding_completed
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property int $subscription_tier_id
 * @property int $subscription_status_id
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Booking> $bookings
 * @property-read int|null $bookings_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Customer> $customers
 * @property-read int|null $customers_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\HandleChange> $handleChanges
 * @property-read int|null $handle_changes_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Location> $locations
 * @property-read int|null $locations_count
 * @property-read \App\Models\User $owner
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Pet> $pets
 * @property-read int|null $pets_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Review> $reviews
 * @property-read int|null $reviews_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Service> $services
 * @property-read int|null $services_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\StaffMember> $staffMembers
 * @property-read int|null $staff_members_count
 * @property-read \App\Models\SubscriptionStatus $subscriptionStatus
 * @property-read \App\Models\SubscriptionTier $subscriptionTier
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Transaction> $transactions
 * @property-read int|null $transactions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $users
 * @property-read int|null $users_count
 *
 * @method static Builder<static>|Business active()
 * @method static \Database\Factories\BusinessFactory factory($count = null, $state = [])
 * @method static Builder<static>|Business newModelQuery()
 * @method static Builder<static>|Business newQuery()
 * @method static Builder<static>|Business onTrial()
 * @method static Builder<static>|Business onlyTrashed()
 * @method static Builder<static>|Business query()
 * @method static Builder<static>|Business tier(string $tierSlug)
 * @method static Builder<static>|Business verified()
 * @method static Builder<static>|Business whereCoverImageUrl($value)
 * @method static Builder<static>|Business whereCreatedAt($value)
 * @method static Builder<static>|Business whereDeletedAt($value)
 * @method static Builder<static>|Business whereDescription($value)
 * @method static Builder<static>|Business whereEmail($value)
 * @method static Builder<static>|Business whereHandle($value)
 * @method static Builder<static>|Business whereId($value)
 * @method static Builder<static>|Business whereIsActive($value)
 * @method static Builder<static>|Business whereLogoUrl($value)
 * @method static Builder<static>|Business whereName($value)
 * @method static Builder<static>|Business whereOwnerUserId($value)
 * @method static Builder<static>|Business wherePhone($value)
 * @method static Builder<static>|Business whereSettings($value)
 * @method static Builder<static>|Business whereSlug($value)
 * @method static Builder<static>|Business whereStripeCustomerId($value)
 * @method static Builder<static>|Business whereStripeSubscriptionId($value)
 * @method static Builder<static>|Business whereSubscriptionStatusId($value)
 * @method static Builder<static>|Business whereSubscriptionTierId($value)
 * @method static Builder<static>|Business whereTrialEndsAt($value)
 * @method static Builder<static>|Business whereUpdatedAt($value)
 * @method static Builder<static>|Business whereVerificationNotes($value)
 * @method static Builder<static>|Business whereVerificationStatus($value)
 * @method static Builder<static>|Business whereVerifiedAt($value)
 * @method static Builder<static>|Business whereWebsite($value)
 * @method static Builder<static>|Business withTrashed(bool $withTrashed = true)
 * @method static Builder<static>|Business withoutTrashed()
 *
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\VerificationDocument> $verificationDocuments
 * @property-read int|null $verification_documents_count
 *
 * @method static Builder<static>|Business whereOnboarding($value)
 * @method static Builder<static>|Business whereOnboardingCompleted($value)
 *
 * @mixin \Eloquent
 */
class Business extends Model
{
    /** @use HasFactory<\Database\Factories\BusinessFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'handle',
        'slug',
        'description',
        'logo_url',
        'cover_image_url',
        'phone',
        'email',
        'website',
        'subscription_tier_id',
        'subscription_status_id',
        'trial_ends_at',
        'stripe_customer_id',
        'stripe_subscription_id',
        'verification_status',
        'verification_notes',
        'verified_at',
        'owner_user_id',
        'is_active',
        'settings',
        'onboarding',
        'onboarding_completed',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'onboarding' => 'array',
            'onboarding_completed' => 'boolean',
            'verified_at' => 'datetime',
            'trial_ends_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    /**
     * @return BelongsTo<SubscriptionTier, $this>
     */
    public function subscriptionTier(): BelongsTo
    {
        return $this->belongsTo(SubscriptionTier::class);
    }

    /**
     * @return BelongsTo<SubscriptionStatus, $this>
     */
    public function subscriptionStatus(): BelongsTo
    {
        return $this->belongsTo(SubscriptionStatus::class);
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('business_role_id', 'is_active', 'invited_at', 'accepted_at')
            ->withTimestamps();
    }

    /**
     * @return HasMany<Location, $this>
     */
    public function locations(): HasMany
    {
        return $this->hasMany(Location::class);
    }

    /**
     * @return HasMany<Service, $this>
     */
    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    /**
     * @return HasMany<Booking, $this>
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * @return HasMany<Customer, $this>
     */
    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    /**
     * @return HasMany<StaffMember, $this>
     */
    public function staffMembers(): HasMany
    {
        return $this->hasMany(StaffMember::class);
    }

    /**
     * @return HasMany<Review, $this>
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * @return HasMany<Transaction, $this>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * @return HasMany<HandleChange, $this>
     */
    public function handleChanges(): HasMany
    {
        return $this->hasMany(HandleChange::class);
    }

    /**
     * @return HasMany<VerificationDocument, $this>
     */
    public function verificationDocuments(): HasMany
    {
        return $this->hasMany(VerificationDocument::class);
    }

    /**
     * @return HasMany<BusinessPageView, $this>
     */
    public function pageViews(): HasMany
    {
        return $this->hasMany(BusinessPageView::class);
    }

    /**
     * @return BelongsToMany<Pet, $this>
     */
    public function pets(): BelongsToMany
    {
        return $this->belongsToMany(Pet::class)
            ->withPivot('notes', 'difficulty_rating', 'last_seen_at')
            ->withTimestamps();
    }

    /**
     * @param  Builder<Business>  $query
     */
    public function scopeVerified(Builder $query): void
    {
        $query->where('verification_status', 'verified');
    }

    /**
     * @param  Builder<Business>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * @param  Builder<Business>  $query
     */
    public function scopeOnTrial(Builder $query): void
    {
        $query->whereHas('subscriptionStatus', fn (Builder $q) => $q->where('slug', 'trial'))
            ->where('trial_ends_at', '>', now());
    }

    /**
     * @param  Builder<Business>  $query
     */
    public function scopeTier(Builder $query, string $tierSlug): void
    {
        $query->whereHas('subscriptionTier', fn (Builder $q) => $q->where('slug', $tierSlug));
    }

    public function isOwner(User $user): bool
    {
        return $this->owner_user_id === $user->id;
    }

    public function hasStaff(User $user): bool
    {
        return $this->users()->where('user_id', $user->id)->exists();
    }

    public function canAccess(User $user): bool
    {
        return $this->isOwner($user) || $this->hasStaff($user);
    }

    public function canAddStaff(): bool
    {
        $tier = $this->subscriptionTier;

        if ($tier->staff_limit <= 0) {
            return false;
        }

        return $this->staffMembers()->where('is_active', true)->count() < $tier->staff_limit;
    }

    public function getAverageRating(): ?float
    {
        return $this->reviews()->where('is_published', true)->avg('rating');
    }

    public function getReviewCount(): int
    {
        return $this->reviews()->where('is_published', true)->count();
    }

    /**
     * @return array<int, int>
     */
    public function getRatingBreakdown(): array
    {
        $breakdown = $this->reviews()
            ->where('is_published', true)
            ->selectRaw('rating, count(*) as count')
            ->groupBy('rating')
            ->pluck('count', 'rating')
            ->toArray();

        return array_replace([1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0], $breakdown);
    }
}
