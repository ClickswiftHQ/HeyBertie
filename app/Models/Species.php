<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property int $sort_order
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Breed> $breeds
 * @property-read int|null $breeds_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Pet> $pets
 * @property-read int|null $pets_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Species newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Species newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Species query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Species whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Species whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Species whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Species whereSortOrder($value)
 *
 * @mixin \Eloquent
 */
class Species extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'name',
        'slug',
        'sort_order',
    ];

    /**
     * @return HasMany<Breed, $this>
     */
    public function breeds(): HasMany
    {
        return $this->hasMany(Breed::class);
    }

    /**
     * @return HasMany<Pet, $this>
     */
    public function pets(): HasMany
    {
        return $this->hasMany(Pet::class);
    }
}
