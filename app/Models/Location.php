<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    /** @use HasFactory<\Database\Factories\LocationFactory> */
    use HasFactory;

    protected $fillable = [
        'business_id',
        'name',
        'slug',
        'location_type',
        'address_line_1',
        'address_line_2',
        'city',
        'postcode',
        'county',
        'latitude',
        'longitude',
        'is_mobile',
        'service_radius_km',
        'phone',
        'email',
        'opening_hours',
        'booking_buffer_minutes',
        'advance_booking_days',
        'min_notice_hours',
        'is_primary',
        'is_active',
        'accepts_bookings',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'opening_hours' => 'array',
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'is_mobile' => 'boolean',
            'is_primary' => 'boolean',
            'is_active' => 'boolean',
            'accepts_bookings' => 'boolean',
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
     * @return HasMany<Booking, $this>
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * @return HasMany<ServiceArea, $this>
     */
    public function serviceAreas(): HasMany
    {
        return $this->hasMany(ServiceArea::class);
    }

    /**
     * @return HasMany<Service, $this>
     */
    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    /**
     * @return HasMany<AvailabilityBlock, $this>
     */
    public function availabilityBlocks(): HasMany
    {
        return $this->hasMany(AvailabilityBlock::class);
    }

    /**
     * @param  Builder<Location>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * @param  Builder<Location>  $query
     */
    public function scopeAcceptingBookings(Builder $query): void
    {
        $query->where('accepts_bookings', true)->where('is_active', true);
    }

    /**
     * @param  Builder<Location>  $query
     */
    public function scopePrimary(Builder $query): void
    {
        $query->where('is_primary', true);
    }

    /**
     * @param  Builder<Location>  $query
     */
    public function scopeMobile(Builder $query): void
    {
        $query->where('is_mobile', true);
    }

    public function isWithinServiceRadius(float $lat, float $lng): bool
    {
        if (! $this->is_mobile || ! $this->service_radius_km) {
            return false;
        }

        return $this->getDistanceFrom($lat, $lng) <= $this->service_radius_km;
    }

    public function servesPostcode(string $postcode): bool
    {
        $prefix = strtoupper(preg_replace('/[0-9].*/', '', $postcode));

        return $this->serviceAreas()->where('postcode_prefix', $prefix)->exists();
    }

    /**
     * Calculate distance using the Haversine formula.
     */
    public function getDistanceFrom(float $lat, float $lng): float
    {
        if (! $this->latitude || ! $this->longitude) {
            return PHP_FLOAT_MAX;
        }

        $earthRadius = 6371; // km

        $latDelta = deg2rad($lat - $this->latitude);
        $lngDelta = deg2rad($lng - $this->longitude);

        $a = sin($latDelta / 2) * sin($latDelta / 2)
            + cos(deg2rad($this->latitude)) * cos(deg2rad($lat))
            * sin($lngDelta / 2) * sin($lngDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
