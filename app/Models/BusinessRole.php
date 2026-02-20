<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property int $sort_order
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessRole newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessRole newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessRole query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessRole whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessRole whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessRole whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessRole whereSortOrder($value)
 *
 * @mixin \Eloquent
 */
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
