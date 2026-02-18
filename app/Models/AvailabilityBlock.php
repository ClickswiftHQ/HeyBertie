<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AvailabilityBlock extends Model
{
    protected $fillable = [
        'business_id',
        'location_id',
        'staff_member_id',
        'day_of_week',
        'start_time',
        'end_time',
        'specific_date',
        'block_type',
        'repeat_weekly',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'specific_date' => 'date',
            'repeat_weekly' => 'boolean',
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
     * @return BelongsTo<StaffMember, $this>
     */
    public function staffMember(): BelongsTo
    {
        return $this->belongsTo(StaffMember::class);
    }

    /**
     * @param  Builder<AvailabilityBlock>  $query
     */
    public function scopeForDate(Builder $query, Carbon $date): void
    {
        $query->where(function (Builder $q) use ($date) {
            $q->where('specific_date', $date->toDateString())
                ->orWhere(function (Builder $q) use ($date) {
                    $q->whereNull('specific_date')
                        ->where('day_of_week', $date->dayOfWeek);
                });
        });
    }

    /**
     * @param  Builder<AvailabilityBlock>  $query
     */
    public function scopeForDayOfWeek(Builder $query, int $day): void
    {
        $query->where('day_of_week', $day);
    }

    /**
     * @param  Builder<AvailabilityBlock>  $query
     */
    public function scopeAvailable(Builder $query): void
    {
        $query->where('block_type', 'available');
    }

    /**
     * @param  Builder<AvailabilityBlock>  $query
     */
    public function scopeBlocked(Builder $query): void
    {
        $query->whereIn('block_type', ['blocked', 'holiday']);
    }

    public function isActiveOn(Carbon $datetime): bool
    {
        if ($this->specific_date) {
            return $this->specific_date->isSameDay($datetime);
        }

        return $this->day_of_week === $datetime->dayOfWeek;
    }

    public function conflictsWith(AvailabilityBlock $other): bool
    {
        return $this->start_time < $other->end_time && $this->end_time > $other->start_time;
    }
}
