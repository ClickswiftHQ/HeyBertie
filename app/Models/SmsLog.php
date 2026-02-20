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
 * @property string $phone_number
 * @property string $message_type
 * @property string $message_body
 * @property string|null $twilio_sid
 * @property string $status
 * @property \Carbon\CarbonImmutable|null $sent_at
 * @property \Carbon\CarbonImmutable|null $delivered_at
 * @property numeric $cost
 * @property bool $charged_to_business
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\Booking|null $booking
 * @property-read \App\Models\Business $business
 *
 * @method static Builder<static>|SmsLog delivered()
 * @method static Builder<static>|SmsLog forBusiness(\App\Models\Business $business)
 * @method static Builder<static>|SmsLog forPeriod(\Carbon\Carbon $start, \Carbon\Carbon $end)
 * @method static Builder<static>|SmsLog newModelQuery()
 * @method static Builder<static>|SmsLog newQuery()
 * @method static Builder<static>|SmsLog query()
 * @method static Builder<static>|SmsLog whereBookingId($value)
 * @method static Builder<static>|SmsLog whereBusinessId($value)
 * @method static Builder<static>|SmsLog whereChargedToBusiness($value)
 * @method static Builder<static>|SmsLog whereCost($value)
 * @method static Builder<static>|SmsLog whereCreatedAt($value)
 * @method static Builder<static>|SmsLog whereDeliveredAt($value)
 * @method static Builder<static>|SmsLog whereId($value)
 * @method static Builder<static>|SmsLog whereMessageBody($value)
 * @method static Builder<static>|SmsLog whereMessageType($value)
 * @method static Builder<static>|SmsLog wherePhoneNumber($value)
 * @method static Builder<static>|SmsLog whereSentAt($value)
 * @method static Builder<static>|SmsLog whereStatus($value)
 * @method static Builder<static>|SmsLog whereTwilioSid($value)
 * @method static Builder<static>|SmsLog whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
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
