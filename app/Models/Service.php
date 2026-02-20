<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $business_id
 * @property int|null $location_id
 * @property string $name
 * @property string|null $description
 * @property int $duration_minutes
 * @property numeric|null $price
 * @property string $price_type
 * @property int $display_order
 * @property bool $is_active
 * @property bool $is_featured
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Booking> $bookings
 * @property-read int|null $bookings_count
 * @property-read \App\Models\Business $business
 * @property-read \App\Models\Location|null $location
 *
 * @method static Builder<static>|Service active()
 * @method static \Database\Factories\ServiceFactory factory($count = null, $state = [])
 * @method static Builder<static>|Service featured()
 * @method static Builder<static>|Service forLocation(\App\Models\Location $location)
 * @method static Builder<static>|Service newModelQuery()
 * @method static Builder<static>|Service newQuery()
 * @method static Builder<static>|Service onlyTrashed()
 * @method static Builder<static>|Service query()
 * @method static Builder<static>|Service whereBusinessId($value)
 * @method static Builder<static>|Service whereCreatedAt($value)
 * @method static Builder<static>|Service whereDeletedAt($value)
 * @method static Builder<static>|Service whereDescription($value)
 * @method static Builder<static>|Service whereDisplayOrder($value)
 * @method static Builder<static>|Service whereDurationMinutes($value)
 * @method static Builder<static>|Service whereId($value)
 * @method static Builder<static>|Service whereIsActive($value)
 * @method static Builder<static>|Service whereIsFeatured($value)
 * @method static Builder<static>|Service whereLocationId($value)
 * @method static Builder<static>|Service whereName($value)
 * @method static Builder<static>|Service wherePrice($value)
 * @method static Builder<static>|Service wherePriceType($value)
 * @method static Builder<static>|Service whereUpdatedAt($value)
 * @method static Builder<static>|Service withTrashed(bool $withTrashed = true)
 * @method static Builder<static>|Service withoutTrashed()
 *
 * @mixin \Eloquent
 */
class Service extends Model
{
    /** @use HasFactory<\Database\Factories\ServiceFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'business_id',
        'location_id',
        'name',
        'description',
        'duration_minutes',
        'price',
        'price_type',
        'display_order',
        'is_active',
        'is_featured',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
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
     * @return BelongsTo<Location, $this>
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * @return HasMany<Booking, $this>
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * @param  Builder<Service>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * @param  Builder<Service>  $query
     */
    public function scopeForLocation(Builder $query, Location $location): void
    {
        $query->where(function (Builder $q) use ($location) {
            $q->where('location_id', $location->id)
                ->orWhereNull('location_id');
        });
    }

    /**
     * @param  Builder<Service>  $query
     */
    public function scopeFeatured(Builder $query): void
    {
        $query->where('is_featured', true);
    }

    public function getFormattedPrice(): string
    {
        return match ($this->price_type) {
            'from' => 'From £'.number_format((float) $this->price, 2),
            'call' => 'Price on request',
            default => '£'.number_format((float) $this->price, 2),
        };
    }

    public function isAvailableAtLocation(Location $location): bool
    {
        return $this->location_id === null || $this->location_id === $location->id;
    }
}
