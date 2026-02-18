<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
