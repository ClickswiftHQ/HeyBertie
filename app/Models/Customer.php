<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    /** @use HasFactory<\Database\Factories\CustomerFactory> */
    use HasFactory;

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
        $this->increment('total_bookings');
        $this->increment('total_spent', (float) $booking->price);
        $this->update(['last_visit' => $booking->appointment_datetime]);
        $this->incrementLoyaltyPoints(10);
    }
}
