<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property int $monthly_price_pence
 * @property int $staff_limit
 * @property int $location_limit
 * @property int $sms_quota
 * @property int $sort_order
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Business> $businesses
 * @property-read int|null $businesses_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionTier newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionTier newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionTier query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionTier whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionTier whereLocationLimit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionTier whereMonthlyPricePence($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionTier whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionTier whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionTier whereSmsQuota($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionTier whereSortOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionTier whereStaffLimit($value)
 *
 * @mixin \Eloquent
 */
class SubscriptionTier extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'name',
        'slug',
        'monthly_price_pence',
        'staff_limit',
        'location_limit',
        'sms_quota',
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
