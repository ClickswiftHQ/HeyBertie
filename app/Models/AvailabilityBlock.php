<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $business_id
 * @property int|null $location_id
 * @property int|null $staff_member_id
 * @property int|null $day_of_week
 * @property string $start_time
 * @property string $end_time
 * @property \Carbon\CarbonImmutable|null $specific_date
 * @property string $block_type
 * @property bool $repeat_weekly
 * @property string|null $notes
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\Business $business
 * @property-read \App\Models\Location|null $location
 * @property-read \App\Models\StaffMember|null $staffMember
 *
 * @method static Builder<static>|AvailabilityBlock available()
 * @method static Builder<static>|AvailabilityBlock blocked()
 * @method static Builder<static>|AvailabilityBlock forDate(\Carbon\Carbon $date)
 * @method static Builder<static>|AvailabilityBlock forDayOfWeek(int $day)
 * @method static Builder<static>|AvailabilityBlock newModelQuery()
 * @method static Builder<static>|AvailabilityBlock newQuery()
 * @method static Builder<static>|AvailabilityBlock query()
 * @method static Builder<static>|AvailabilityBlock whereBlockType($value)
 * @method static Builder<static>|AvailabilityBlock whereBusinessId($value)
 * @method static Builder<static>|AvailabilityBlock whereCreatedAt($value)
 * @method static Builder<static>|AvailabilityBlock whereDayOfWeek($value)
 * @method static Builder<static>|AvailabilityBlock whereEndTime($value)
 * @method static Builder<static>|AvailabilityBlock whereId($value)
 * @method static Builder<static>|AvailabilityBlock whereLocationId($value)
 * @method static Builder<static>|AvailabilityBlock whereNotes($value)
 * @method static Builder<static>|AvailabilityBlock whereRepeatWeekly($value)
 * @method static Builder<static>|AvailabilityBlock whereSpecificDate($value)
 * @method static Builder<static>|AvailabilityBlock whereStaffMemberId($value)
 * @method static Builder<static>|AvailabilityBlock whereStartTime($value)
 * @method static Builder<static>|AvailabilityBlock whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
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
            $q->whereDate('specific_date', $date->toDateString())
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
