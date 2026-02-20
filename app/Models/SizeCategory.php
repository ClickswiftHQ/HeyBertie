<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property int $sort_order
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Pet> $pets
 * @property-read int|null $pets_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SizeCategory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SizeCategory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SizeCategory query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SizeCategory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SizeCategory whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SizeCategory whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SizeCategory whereSortOrder($value)
 *
 * @mixin \Eloquent
 */
class SizeCategory extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'name',
        'slug',
        'sort_order',
    ];

    /**
     * @return HasMany<Pet, $this>
     */
    public function pets(): HasMany
    {
        return $this->hasMany(Pet::class);
    }
}
