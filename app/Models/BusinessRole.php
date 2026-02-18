<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessRole extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'name',
        'slug',
        'sort_order',
    ];

    public static function findBySlug(string $slug): ?self
    {
        return static::where('slug', $slug)->first();
    }
}
