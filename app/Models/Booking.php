<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $business_id
 * @property int $location_id
 * @property int|null $service_id
 * @property int $customer_id
 * @property int|null $staff_member_id
 * @property \Carbon\CarbonImmutable $appointment_datetime
 * @property int $duration_minutes
 * @property string $status
 * @property string|null $booking_reference
 * @property numeric $price
 * @property numeric $deposit_amount
 * @property bool $deposit_paid
 * @property string $payment_status
 * @property string|null $payment_intent_id
 * @property string|null $customer_notes
 * @property string|null $pet_name
 * @property string|null $pet_breed
 * @property string|null $pet_size
 * @property string|null $pro_notes
 * @property \Carbon\CarbonImmutable|null $reminder_sent_at
 * @property \Carbon\CarbonImmutable|null $reminder_2hr_sent_at
 * @property int|null $cancelled_by_user_id
 * @property \Carbon\CarbonImmutable|null $cancelled_at
 * @property string|null $cancellation_reason
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property-read \App\Models\Business $business
 * @property-read \App\Models\User|null $cancelledBy
 * @property-read \App\Models\Customer $customer
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\BookingItem> $items
 * @property-read int|null $items_count
 * @property-read \App\Models\Location $location
 * @property-read \App\Models\Service|null $service
 * @property-read \App\Models\StaffMember|null $staffMember
 *
 * @method static \Database\Factories\BookingFactory factory($count = null, $state = [])
 * @method static Builder<static>|Booking forCustomer(\App\Models\Customer $customer)
 * @method static Builder<static>|Booking forStaff(\App\Models\StaffMember $staff)
 * @method static Builder<static>|Booking needsReminder()
 * @method static Builder<static>|Booking newModelQuery()
 * @method static Builder<static>|Booking newQuery()
 * @method static Builder<static>|Booking onlyTrashed()
 * @method static Builder<static>|Booking past()
 * @method static Builder<static>|Booking query()
 * @method static Builder<static>|Booking status(string $status)
 * @method static Builder<static>|Booking upcoming()
 * @method static Builder<static>|Booking whereAppointmentDatetime($value)
 * @method static Builder<static>|Booking whereBusinessId($value)
 * @method static Builder<static>|Booking whereCancellationReason($value)
 * @method static Builder<static>|Booking whereCancelledAt($value)
 * @method static Builder<static>|Booking whereCancelledByUserId($value)
 * @method static Builder<static>|Booking whereCreatedAt($value)
 * @method static Builder<static>|Booking whereCustomerId($value)
 * @method static Builder<static>|Booking whereCustomerNotes($value)
 * @method static Builder<static>|Booking whereDeletedAt($value)
 * @method static Builder<static>|Booking whereDepositAmount($value)
 * @method static Builder<static>|Booking whereDepositPaid($value)
 * @method static Builder<static>|Booking whereDurationMinutes($value)
 * @method static Builder<static>|Booking whereId($value)
 * @method static Builder<static>|Booking whereLocationId($value)
 * @method static Builder<static>|Booking wherePaymentIntentId($value)
 * @method static Builder<static>|Booking wherePaymentStatus($value)
 * @method static Builder<static>|Booking wherePrice($value)
 * @method static Builder<static>|Booking whereProNotes($value)
 * @method static Builder<static>|Booking whereReminder2hrSentAt($value)
 * @method static Builder<static>|Booking whereReminderSentAt($value)
 * @method static Builder<static>|Booking whereServiceId($value)
 * @method static Builder<static>|Booking whereStaffMemberId($value)
 * @method static Builder<static>|Booking whereStatus($value)
 * @method static Builder<static>|Booking whereUpdatedAt($value)
 * @method static Builder<static>|Booking withTrashed(bool $withTrashed = true)
 * @method static Builder<static>|Booking withoutTrashed()
 *
 * @mixin \Eloquent
 */
class Booking extends Model
{
    /** @use HasFactory<\Database\Factories\BookingFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'business_id',
        'location_id',
        'service_id',
        'customer_id',
        'staff_member_id',
        'appointment_datetime',
        'duration_minutes',
        'status',
        'booking_reference',
        'price',
        'deposit_amount',
        'deposit_paid',
        'payment_status',
        'payment_intent_id',
        'customer_notes',
        'pet_name',
        'pet_breed',
        'pet_size',
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
     * @return HasMany<BookingItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(BookingItem::class)->orderBy('display_order');
    }

    public static function generateReference(): string
    {
        do {
            $reference = 'BK-'.Str::upper(Str::random(6));
        } while (static::where('booking_reference', $reference)->exists());

        return $reference;
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

        return $this->appointment_datetime->isAfter(now()->addHours(24));
    }

    public function canBeRescheduled(): bool
    {
        if (in_array($this->status, ['cancelled', 'completed', 'no_show'])) {
            return false;
        }

        return $this->appointment_datetime->isAfter(now()->addHours(24));
    }

    public function cancel(User $user, ?string $reason = null): void
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
