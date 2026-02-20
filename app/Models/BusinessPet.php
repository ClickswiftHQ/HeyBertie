<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @method static self updateOrCreate(array $attributes, array $values = [])
 *
 * @property int $id
 * @property int $business_id
 * @property int $pet_id
 * @property string|null $notes
 * @property int|null $difficulty_rating
 * @property \Carbon\CarbonImmutable|null $last_seen_at
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\Business $business
 * @property-read \App\Models\Pet $pet
 *
 * @method static \Database\Factories\BusinessPetFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessPet newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessPet newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessPet query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessPet whereBusinessId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessPet whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessPet whereDifficultyRating($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessPet whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessPet whereLastSeenAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessPet whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessPet wherePetId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessPet whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class BusinessPet extends Model
{
    /** @use HasFactory<\Database\Factories\BusinessPetFactory> */
    use HasFactory;

    protected $table = 'business_pet';

    protected $fillable = [
        'business_id',
        'pet_id',
        'notes',
        'difficulty_rating',
        'last_seen_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'difficulty_rating' => 'integer',
            'last_seen_at' => 'datetime',
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
     * @return BelongsTo<Pet, $this>
     */
    public function pet(): BelongsTo
    {
        return $this->belongsTo(Pet::class);
    }
}
