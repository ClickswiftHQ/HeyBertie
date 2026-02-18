<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
