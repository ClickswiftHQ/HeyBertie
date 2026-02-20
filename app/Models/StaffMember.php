<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $business_id
 * @property int $user_id
 * @property string $display_name
 * @property string|null $bio
 * @property string|null $photo_url
 * @property string $role
 * @property numeric $commission_rate
 * @property string $calendar_color
 * @property bool $accepts_online_bookings
 * @property bool $is_active
 * @property \Carbon\CarbonImmutable|null $employed_since
 * @property \Carbon\CarbonImmutable|null $left_at
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Booking> $bookings
 * @property-read int|null $bookings_count
 * @property-read \App\Models\Business $business
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Location> $locations
 * @property-read int|null $locations_count
 * @property-read \App\Models\User $user
 *
 * @method static Builder<static>|StaffMember acceptingBookings()
 * @method static Builder<static>|StaffMember active()
 * @method static \Database\Factories\StaffMemberFactory factory($count = null, $state = [])
 * @method static Builder<static>|StaffMember newModelQuery()
 * @method static Builder<static>|StaffMember newQuery()
 * @method static Builder<static>|StaffMember onlyTrashed()
 * @method static Builder<static>|StaffMember query()
 * @method static Builder<static>|StaffMember whereAcceptsOnlineBookings($value)
 * @method static Builder<static>|StaffMember whereBio($value)
 * @method static Builder<static>|StaffMember whereBusinessId($value)
 * @method static Builder<static>|StaffMember whereCalendarColor($value)
 * @method static Builder<static>|StaffMember whereCommissionRate($value)
 * @method static Builder<static>|StaffMember whereCreatedAt($value)
 * @method static Builder<static>|StaffMember whereDeletedAt($value)
 * @method static Builder<static>|StaffMember whereDisplayName($value)
 * @method static Builder<static>|StaffMember whereEmployedSince($value)
 * @method static Builder<static>|StaffMember whereId($value)
 * @method static Builder<static>|StaffMember whereIsActive($value)
 * @method static Builder<static>|StaffMember whereLeftAt($value)
 * @method static Builder<static>|StaffMember wherePhotoUrl($value)
 * @method static Builder<static>|StaffMember whereRole($value)
 * @method static Builder<static>|StaffMember whereUpdatedAt($value)
 * @method static Builder<static>|StaffMember whereUserId($value)
 * @method static Builder<static>|StaffMember withTrashed(bool $withTrashed = true)
 * @method static Builder<static>|StaffMember withoutTrashed()
 * @method static Builder<static>|StaffMember worksAtLocation(\App\Models\Location $location)
 *
 * @mixin \Eloquent
 */
class StaffMember extends Model
{
    /** @use HasFactory<\Database\Factories\StaffMemberFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'business_id',
        'user_id',
        'display_name',
        'bio',
        'photo_url',
        'role',
        'commission_rate',
        'calendar_color',
        'accepts_online_bookings',
        'is_active',
        'employed_since',
        'left_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'commission_rate' => 'decimal:2',
            'employed_since' => 'datetime',
            'left_at' => 'datetime',
            'is_active' => 'boolean',
            'accepts_online_bookings' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Business, $this>
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsToMany<Location, $this>
     */
    public function locations(): BelongsToMany
    {
        return $this->belongsToMany(Location::class, 'staff_location');
    }

    /**
     * @return HasMany<Booking, $this>
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * @param  Builder<StaffMember>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * @param  Builder<StaffMember>  $query
     */
    public function scopeAcceptingBookings(Builder $query): void
    {
        $query->where('accepts_online_bookings', true)->where('is_active', true);
    }

    /**
     * @param  Builder<StaffMember>  $query
     */
    public function scopeWorksAtLocation(Builder $query, Location $location): void
    {
        $query->whereHas('locations', fn (Builder $q) => $q->where('locations.id', $location->id));
    }

    public function getEarningsForPeriod(Carbon $start, Carbon $end): float
    {
        $totalRevenue = $this->bookings()
            ->where('status', 'completed')
            ->whereBetween('appointment_datetime', [$start, $end])
            ->sum('price');

        return round($totalRevenue * ((float) $this->commission_rate / 100), 2);
    }

    public function getBookingCountForPeriod(Carbon $start, Carbon $end): int
    {
        return $this->bookings()
            ->whereBetween('appointment_datetime', [$start, $end])
            ->count();
    }
}
