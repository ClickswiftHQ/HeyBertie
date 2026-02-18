<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    /** @use HasFactory<\Database\Factories\ServiceFactory> */
    use HasFactory;

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
