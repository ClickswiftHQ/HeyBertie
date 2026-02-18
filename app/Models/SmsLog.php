<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsLog extends Model
{
    protected $table = 'sms_log';

    protected $fillable = [
        'business_id',
        'booking_id',
        'phone_number',
        'message_type',
        'message_body',
        'twilio_sid',
        'status',
        'sent_at',
        'delivered_at',
        'cost',
        'charged_to_business',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'delivered_at' => 'datetime',
            'cost' => 'decimal:4',
            'charged_to_business' => 'boolean',
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
     * @param  Builder<SmsLog>  $query
     */
    public function scopeForBusiness(Builder $query, Business $business): void
    {
        $query->where('business_id', $business->id);
    }

    /**
     * @param  Builder<SmsLog>  $query
     */
    public function scopeForPeriod(Builder $query, Carbon $start, Carbon $end): void
    {
        $query->whereBetween('created_at', [$start, $end]);
    }

    /**
     * @param  Builder<SmsLog>  $query
     */
    public function scopeDelivered(Builder $query): void
    {
        $query->where('status', 'delivered');
    }
}
