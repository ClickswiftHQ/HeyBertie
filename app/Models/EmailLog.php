<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
