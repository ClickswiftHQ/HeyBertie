<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
