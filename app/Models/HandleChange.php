<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $business_id
 * @property string $old_handle
 * @property string $new_handle
 * @property int $changed_by_user_id
 * @property \Carbon\CarbonImmutable $changed_at
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\Business $business
 * @property-read \App\Models\User $changedBy
 *
 * @method static Builder<static>|HandleChange forHandle(string $handle)
 * @method static Builder<static>|HandleChange newModelQuery()
 * @method static Builder<static>|HandleChange newQuery()
 * @method static Builder<static>|HandleChange query()
 * @method static Builder<static>|HandleChange whereBusinessId($value)
 * @method static Builder<static>|HandleChange whereChangedAt($value)
 * @method static Builder<static>|HandleChange whereChangedByUserId($value)
 * @method static Builder<static>|HandleChange whereCreatedAt($value)
 * @method static Builder<static>|HandleChange whereId($value)
 * @method static Builder<static>|HandleChange whereNewHandle($value)
 * @method static Builder<static>|HandleChange whereOldHandle($value)
 * @method static Builder<static>|HandleChange whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class HandleChange extends Model
{
    protected $fillable = [
        'business_id',
        'old_handle',
        'new_handle',
        'changed_by_user_id',
        'changed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'changed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Business, $this>
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by_user_id');
    }

    /**
     * @param  Builder<HandleChange>  $query
     */
    public function scopeForHandle(Builder $query, string $handle): void
    {
        $query->where('old_handle', $handle);
    }
}
