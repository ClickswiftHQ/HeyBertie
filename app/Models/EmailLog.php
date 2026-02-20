<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int|null $business_id
 * @property int|null $booking_id
 * @property string $to_email
 * @property string $email_type
 * @property string $subject
 * @property string|null $postmark_message_id
 * @property string $status
 * @property \Carbon\CarbonImmutable|null $sent_at
 * @property \Carbon\CarbonImmutable|null $opened_at
 * @property \Carbon\CarbonImmutable|null $clicked_at
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\Booking|null $booking
 * @property-read \App\Models\Business|null $business
 *
 * @method static Builder<static>|EmailLog delivered()
 * @method static Builder<static>|EmailLog forBusiness(\App\Models\Business $business)
 * @method static Builder<static>|EmailLog forPeriod(\Carbon\Carbon $start, \Carbon\Carbon $end)
 * @method static Builder<static>|EmailLog newModelQuery()
 * @method static Builder<static>|EmailLog newQuery()
 * @method static Builder<static>|EmailLog query()
 * @method static Builder<static>|EmailLog whereBookingId($value)
 * @method static Builder<static>|EmailLog whereBusinessId($value)
 * @method static Builder<static>|EmailLog whereClickedAt($value)
 * @method static Builder<static>|EmailLog whereCreatedAt($value)
 * @method static Builder<static>|EmailLog whereEmailType($value)
 * @method static Builder<static>|EmailLog whereId($value)
 * @method static Builder<static>|EmailLog whereOpenedAt($value)
 * @method static Builder<static>|EmailLog wherePostmarkMessageId($value)
 * @method static Builder<static>|EmailLog whereSentAt($value)
 * @method static Builder<static>|EmailLog whereStatus($value)
 * @method static Builder<static>|EmailLog whereSubject($value)
 * @method static Builder<static>|EmailLog whereToEmail($value)
 * @method static Builder<static>|EmailLog whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class EmailLog extends Model
{
    protected $table = 'email_log';

    protected $fillable = [
        'business_id',
        'booking_id',
        'to_email',
        'email_type',
        'subject',
        'postmark_message_id',
        'status',
        'sent_at',
        'opened_at',
        'clicked_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'opened_at' => 'datetime',
            'clicked_at' => 'datetime',
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
     * @param  Builder<EmailLog>  $query
     */
    public function scopeForBusiness(Builder $query, Business $business): void
    {
        $query->where('business_id', $business->id);
    }

    /**
     * @param  Builder<EmailLog>  $query
     */
    public function scopeForPeriod(Builder $query, Carbon $start, Carbon $end): void
    {
        $query->whereBetween('created_at', [$start, $end]);
    }

    /**
     * @param  Builder<EmailLog>  $query
     */
    public function scopeDelivered(Builder $query): void
    {
        $query->where('status', 'delivered');
    }
}
