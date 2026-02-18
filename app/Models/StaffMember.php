<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StaffMember extends Model
{
    /** @use HasFactory<\Database\Factories\StaffMemberFactory> */
    use HasFactory;

    protected $fillable = [
        'business_id',
        'user_id',
        'display_name',
        'bio',
        'photo_url',
        'role',
        'commission_rate',
        'calendar_color',
        'working_locations',
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
            'working_locations' => 'array',
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
        $query->whereJsonContains('working_locations', $location->id);
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
