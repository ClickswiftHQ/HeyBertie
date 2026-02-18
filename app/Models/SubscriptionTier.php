<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
