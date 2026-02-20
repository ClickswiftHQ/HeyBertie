<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property int $sort_order
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Business> $businesses
 * @property-read int|null $businesses_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionStatus newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionStatus newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionStatus query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionStatus whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionStatus whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionStatus whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionStatus whereSortOrder($value)
 *
 * @mixin \Eloquent
 */
class SubscriptionStatus extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'name',
        'slug',
        'sort_order',
    ];

    /**
     * @return HasMany<Business, $this>
     */
    public function businesses(): HasMany
    {
        return $this->hasMany(Business::class);
    }

    public static function findBySlug(string $slug): ?self
    {
        return static::where('slug', $slug)->first();
    }
}
