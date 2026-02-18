<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
