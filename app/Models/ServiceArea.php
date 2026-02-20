<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $location_id
 * @property string $area_name
 * @property string $postcode_prefix
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\Location $location
 *
 * @method static Builder<static>|ServiceArea forPostcode(string $postcode)
 * @method static Builder<static>|ServiceArea newModelQuery()
 * @method static Builder<static>|ServiceArea newQuery()
 * @method static Builder<static>|ServiceArea query()
 * @method static Builder<static>|ServiceArea whereAreaName($value)
 * @method static Builder<static>|ServiceArea whereCreatedAt($value)
 * @method static Builder<static>|ServiceArea whereId($value)
 * @method static Builder<static>|ServiceArea whereLocationId($value)
 * @method static Builder<static>|ServiceArea wherePostcodePrefix($value)
 * @method static Builder<static>|ServiceArea whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class ServiceArea extends Model
{
    protected $fillable = [
        'location_id',
        'area_name',
        'postcode_prefix',
    ];

    /**
     * @return BelongsTo<Location, $this>
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * @param  Builder<ServiceArea>  $query
     */
    public function scopeForPostcode(Builder $query, string $postcode): void
    {
        $prefix = strtoupper(preg_replace('/[0-9].*/', '', $postcode));
        $query->where('postcode_prefix', $prefix);
    }
}
