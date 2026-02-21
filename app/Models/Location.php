<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $business_id
 * @property string $name
 * @property string $slug
 * @property string $location_type
 * @property string $address_line_1
 * @property string|null $address_line_2
 * @property string $town
 * @property string $city
 * @property string $postcode
 * @property string|null $county
 * @property numeric|null $latitude
 * @property numeric|null $longitude
 * @property bool $is_mobile
 * @property int|null $service_radius_km
 * @property string|null $phone
 * @property string|null $email
 * @property array<array-key, mixed>|null $opening_hours
 * @property int $booking_buffer_minutes
 * @property int $advance_booking_days
 * @property int $min_notice_hours
 * @property bool $is_primary
 * @property bool $is_active
 * @property bool $accepts_bookings
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\AvailabilityBlock> $availabilityBlocks
 * @property-read int|null $availability_blocks_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Booking> $bookings
 * @property-read int|null $bookings_count
 * @property-read \App\Models\Business $business
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ServiceArea> $serviceAreas
 * @property-read int|null $service_areas_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Service> $services
 * @property-read int|null $services_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\StaffMember> $staffMembers
 * @property-read int|null $staff_members_count
 *
 * @method static Builder<static>|Location acceptingBookings()
 * @method static Builder<static>|Location active()
 * @method static \Database\Factories\LocationFactory factory($count = null, $state = [])
 * @method static Builder<static>|Location mobile()
 * @method static Builder<static>|Location newModelQuery()
 * @method static Builder<static>|Location newQuery()
 * @method static Builder<static>|Location primary()
 * @method static Builder<static>|Location query()
 * @method static Builder<static>|Location whereAcceptsBookings($value)
 * @method static Builder<static>|Location whereAddressLine1($value)
 * @method static Builder<static>|Location whereAddressLine2($value)
 * @method static Builder<static>|Location whereAdvanceBookingDays($value)
 * @method static Builder<static>|Location whereBookingBufferMinutes($value)
 * @method static Builder<static>|Location whereBusinessId($value)
 * @method static Builder<static>|Location whereCity($value)
 * @method static Builder<static>|Location whereCounty($value)
 * @method static Builder<static>|Location whereCreatedAt($value)
 * @method static Builder<static>|Location whereEmail($value)
 * @method static Builder<static>|Location whereId($value)
 * @method static Builder<static>|Location whereIsActive($value)
 * @method static Builder<static>|Location whereIsMobile($value)
 * @method static Builder<static>|Location whereIsPrimary($value)
 * @method static Builder<static>|Location whereLatitude($value)
 * @method static Builder<static>|Location whereLocationType($value)
 * @method static Builder<static>|Location whereLongitude($value)
 * @method static Builder<static>|Location whereMinNoticeHours($value)
 * @method static Builder<static>|Location whereName($value)
 * @method static Builder<static>|Location whereOpeningHours($value)
 * @method static Builder<static>|Location wherePhone($value)
 * @method static Builder<static>|Location wherePostcode($value)
 * @method static Builder<static>|Location whereServiceRadiusKm($value)
 * @method static Builder<static>|Location whereSlug($value)
 * @method static Builder<static>|Location whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
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
        'town',
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
     * @return BelongsToMany<StaffMember, $this>
     */
    public function staffMembers(): BelongsToMany
    {
        return $this->belongsToMany(StaffMember::class, 'staff_location');
    }

    /**
     * @return HasMany<AvailabilityBlock, $this>
     */
    public function availabilityBlocks(): HasMany
    {
        return $this->hasMany(AvailabilityBlock::class);
    }

    public static function generateSlug(string $town, string $city): string
    {
        $town = trim($town);
        $city = trim($city);

        if (mb_strtolower($town) === mb_strtolower($city)) {
            return Str::slug($town);
        }

        return Str::slug($town.' '.$city);
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
