<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

/**
 * @property int $id
 * @property int $business_id
 * @property int $user_id
 * @property string $name
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $address
 * @property int $loyalty_points
 * @property int $total_bookings
 * @property numeric $total_spent
 * @property \Carbon\CarbonImmutable|null $last_visit
 * @property \Carbon\CarbonImmutable|null $birthday
 * @property bool $is_active
 * @property array<array-key, mixed>|null $tags
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property string $source
 * @property bool $marketing_consent
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Booking> $bookings
 * @property-read int|null $bookings_count
 * @property-read \App\Models\Business $business
 * @property-read \App\Models\User $user
 *
 * @method static Builder<static>|Customer active()
 * @method static \Database\Factories\CustomerFactory factory($count = null, $state = [])
 * @method static Builder<static>|Customer hasTag(string $tag)
 * @method static Builder<static>|Customer newModelQuery()
 * @method static Builder<static>|Customer newQuery()
 * @method static Builder<static>|Customer onlyTrashed()
 * @method static Builder<static>|Customer query()
 * @method static Builder<static>|Customer vip()
 * @method static Builder<static>|Customer whereAddress($value)
 * @method static Builder<static>|Customer whereBirthday($value)
 * @method static Builder<static>|Customer whereBusinessId($value)
 * @method static Builder<static>|Customer whereCreatedAt($value)
 * @method static Builder<static>|Customer whereDeletedAt($value)
 * @method static Builder<static>|Customer whereEmail($value)
 * @method static Builder<static>|Customer whereId($value)
 * @method static Builder<static>|Customer whereIsActive($value)
 * @method static Builder<static>|Customer whereLastVisit($value)
 * @method static Builder<static>|Customer whereLoyaltyPoints($value)
 * @method static Builder<static>|Customer whereMarketingConsent($value)
 * @method static Builder<static>|Customer whereName($value)
 * @method static Builder<static>|Customer wherePhone($value)
 * @method static Builder<static>|Customer whereSource($value)
 * @method static Builder<static>|Customer whereTags($value)
 * @method static Builder<static>|Customer whereTotalBookings($value)
 * @method static Builder<static>|Customer whereTotalSpent($value)
 * @method static Builder<static>|Customer whereUpdatedAt($value)
 * @method static Builder<static>|Customer whereUserId($value)
 * @method static Builder<static>|Customer withTrashed(bool $withTrashed = true)
 * @method static Builder<static>|Customer withoutTrashed()
 *
 * @mixin \Eloquent
 */
class Customer extends Model
{
    /** @use HasFactory<\Database\Factories\CustomerFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'business_id',
        'user_id',
        'name',
        'email',
        'phone',
        'address',
        'source',
        'marketing_consent',
        'loyalty_points',
        'total_bookings',
        'total_spent',
        'last_visit',
        'birthday',
        'is_active',
        'tags',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'birthday' => 'date',
            'last_visit' => 'datetime',
            'tags' => 'array',
            'total_spent' => 'decimal:2',
            'is_active' => 'boolean',
            'marketing_consent' => 'boolean',
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
     * @return HasMany<Booking, $this>
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * @param  Builder<Customer>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * @param  Builder<Customer>  $query
     */
    public function scopeHasTag(Builder $query, string $tag): void
    {
        $query->whereJsonContains('tags', $tag);
    }

    /**
     * @param  Builder<Customer>  $query
     */
    public function scopeVip(Builder $query): void
    {
        $query->where('total_spent', '>=', 500);
    }

    public function incrementLoyaltyPoints(int $amount): void
    {
        $this->increment('loyalty_points', $amount);
    }

    public function updateFromBooking(Booking $booking): void
    {
        $this->query()->where('id', $this->id)->update([
            'total_bookings' => DB::raw('total_bookings + 1'),
            'total_spent' => DB::raw('total_spent + '.(float) $booking->price),
            'loyalty_points' => DB::raw('loyalty_points + 10'),
            'last_visit' => $booking->appointment_datetime,
            'updated_at' => now(),
        ]);

        $this->refresh();
    }
}
