<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $business_id
 * @property int|null $location_id
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property string|null $referrer
 * @property string $source
 * @property \Carbon\CarbonImmutable $viewed_at
 * @property-read \App\Models\Business $business
 * @property-read \App\Models\Location|null $location
 *
 * @method static Builder<static>|BusinessPageView forBusiness(\App\Models\Business $business)
 * @method static Builder<static>|BusinessPageView forPeriod(\Carbon\Carbon $start, \Carbon\Carbon $end)
 * @method static \Database\Factories\BusinessPageViewFactory factory($count = null, $state = [])
 * @method static Builder<static>|BusinessPageView newModelQuery()
 * @method static Builder<static>|BusinessPageView newQuery()
 * @method static Builder<static>|BusinessPageView query()
 *
 * @mixin \Eloquent
 */
class BusinessPageView extends Model
{
    /** @use HasFactory<\Database\Factories\BusinessPageViewFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'business_id',
        'location_id',
        'ip_address',
        'user_agent',
        'referrer',
        'source',
        'viewed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'viewed_at' => 'datetime',
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
     * @param  Builder<BusinessPageView>  $query
     */
    public function scopeForBusiness(Builder $query, Business $business): void
    {
        $query->where('business_id', $business->id);
    }

    /**
     * @param  Builder<BusinessPageView>  $query
     */
    public function scopeForPeriod(Builder $query, Carbon $start, Carbon $end): void
    {
        $query->whereBetween('viewed_at', [$start, $end]);
    }
}
