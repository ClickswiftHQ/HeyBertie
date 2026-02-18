<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Booking extends Model
{
    /** @use HasFactory<\Database\Factories\BookingFactory> */
    use HasFactory;

    protected $fillable = [
        'business_id',
        'location_id',
        'service_id',
        'customer_id',
        'staff_member_id',
        'appointment_datetime',
        'duration_minutes',
        'status',
        'price',
        'deposit_amount',
        'deposit_paid',
        'payment_status',
        'payment_intent_id',
        'customer_notes',
        'pro_notes',
        'reminder_sent_at',
        'reminder_2hr_sent_at',
        'cancelled_by_user_id',
        'cancelled_at',
        'cancellation_reason',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'appointment_datetime' => 'datetime',
            'reminder_sent_at' => 'datetime',
            'reminder_2hr_sent_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'price' => 'decimal:2',
            'deposit_amount' => 'decimal:2',
            'deposit_paid' => 'boolean',
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
     * @return BelongsTo<Service, $this>
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return BelongsTo<StaffMember, $this>
     */
    public function staffMember(): BelongsTo
    {
        return $this->belongsTo(StaffMember::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by_user_id');
    }

    /**
     * @param  Builder<Booking>  $query
     */
    public function scopeUpcoming(Builder $query): void
    {
        $query->where('appointment_datetime', '>', now())
            ->whereNotIn('status', ['cancelled', 'no_show']);
    }

    /**
     * @param  Builder<Booking>  $query
     */
    public function scopePast(Builder $query): void
    {
        $query->where('appointment_datetime', '<=', now());
    }

    /**
     * @param  Builder<Booking>  $query
     */
    public function scopeStatus(Builder $query, string $status): void
    {
        $query->where('status', $status);
    }

    /**
     * @param  Builder<Booking>  $query
     */
    public function scopeForStaff(Builder $query, StaffMember $staff): void
    {
        $query->where('staff_member_id', $staff->id);
    }

    /**
     * @param  Builder<Booking>  $query
     */
    public function scopeForCustomer(Builder $query, Customer $customer): void
    {
        $query->where('customer_id', $customer->id);
    }

    /**
     * @param  Builder<Booking>  $query
     */
    public function scopeNeedsReminder(Builder $query): void
    {
        $query->whereNull('reminder_sent_at')
            ->where('status', 'confirmed')
            ->where('appointment_datetime', '<=', now()->addHours(24))
            ->where('appointment_datetime', '>', now());
    }

    public function canBeCancelled(): bool
    {
        if (in_array($this->status, ['cancelled', 'completed', 'no_show'])) {
            return false;
        }

        return $this->appointment_datetime->diffInHours(now()) >= 24;
    }

    public function cancel(User $user, string $reason): void
    {
        $this->update([
            'status' => 'cancelled',
            'cancelled_by_user_id' => $user->id,
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
        ]);
    }

    public function markAsCompleted(): void
    {
        $this->update(['status' => 'completed']);
    }

    public function markAsNoShow(): void
    {
        $this->update(['status' => 'no_show']);
    }
}
