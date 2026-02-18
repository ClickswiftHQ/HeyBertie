<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

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
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'settings' => 'array',
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
