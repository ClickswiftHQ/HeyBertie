<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $business_id
 * @property int|null $booking_id
 * @property string $type
 * @property numeric $amount
 * @property string $currency
 * @property string|null $stripe_payment_intent_id
 * @property string|null $stripe_charge_id
 * @property string|null $stripe_invoice_id
 * @property string $status
 * @property string|null $description
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\Booking|null $booking
 * @property-read \App\Models\Business $business
 *
 * @method static Builder<static>|Transaction completed()
 * @method static Builder<static>|Transaction forPeriod(\Carbon\Carbon $start, \Carbon\Carbon $end)
 * @method static Builder<static>|Transaction newModelQuery()
 * @method static Builder<static>|Transaction newQuery()
 * @method static Builder<static>|Transaction query()
 * @method static Builder<static>|Transaction type(string $type)
 * @method static Builder<static>|Transaction whereAmount($value)
 * @method static Builder<static>|Transaction whereBookingId($value)
 * @method static Builder<static>|Transaction whereBusinessId($value)
 * @method static Builder<static>|Transaction whereCreatedAt($value)
 * @method static Builder<static>|Transaction whereCurrency($value)
 * @method static Builder<static>|Transaction whereDescription($value)
 * @method static Builder<static>|Transaction whereId($value)
 * @method static Builder<static>|Transaction whereStatus($value)
 * @method static Builder<static>|Transaction whereStripeChargeId($value)
 * @method static Builder<static>|Transaction whereStripeInvoiceId($value)
 * @method static Builder<static>|Transaction whereStripePaymentIntentId($value)
 * @method static Builder<static>|Transaction whereType($value)
 * @method static Builder<static>|Transaction whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class Transaction extends Model
{
    protected $fillable = [
        'business_id',
        'booking_id',
        'type',
        'amount',
        'currency',
        'stripe_payment_intent_id',
        'stripe_charge_id',
        'stripe_invoice_id',
        'status',
        'description',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
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
     * @return BelongsTo<Booking, $this>
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * @param  Builder<Transaction>  $query
     */
    public function scopeType(Builder $query, string $type): void
    {
        $query->where('type', $type);
    }

    /**
     * @param  Builder<Transaction>  $query
     */
    public function scopeCompleted(Builder $query): void
    {
        $query->where('status', 'completed');
    }

    /**
     * @param  Builder<Transaction>  $query
     */
    public function scopeForPeriod(Builder $query, Carbon $start, Carbon $end): void
    {
        $query->whereBetween('created_at', [$start, $end]);
    }
}
