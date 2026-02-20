<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property int $species_id
 * @property int|null $breed_id
 * @property int|null $size_category_id
 * @property \Carbon\CarbonImmutable|null $birthday
 * @property string|null $notes
 * @property bool $is_active
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\Breed|null $breed
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\BusinessPet> $businessNotes
 * @property-read int|null $business_notes_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Business> $businesses
 * @property-read int|null $businesses_count
 * @property-read \App\Models\SizeCategory|null $sizeCategory
 * @property-read \App\Models\Species $species
 * @property-read \App\Models\User $user
 *
 * @method static \Database\Factories\PetFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pet newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pet newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pet query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pet whereBirthday($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pet whereBreedId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pet whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pet whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pet whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pet whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pet whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pet whereSizeCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pet whereSpeciesId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pet whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pet whereUserId($value)
 *
 * @mixin \Eloquent
 */
class Pet extends Model
{
    /** @use HasFactory<\Database\Factories\PetFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'species_id',
        'breed_id',
        'size_category_id',
        'birthday',
        'notes',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'birthday' => 'date',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Species, $this>
     */
    public function species(): BelongsTo
    {
        return $this->belongsTo(Species::class);
    }

    /**
     * @return BelongsTo<Breed, $this>
     */
    public function breed(): BelongsTo
    {
        return $this->belongsTo(Breed::class);
    }

    /**
     * @return BelongsTo<SizeCategory, $this>
     */
    public function sizeCategory(): BelongsTo
    {
        return $this->belongsTo(SizeCategory::class);
    }

    /**
     * @return BelongsToMany<Business, $this>
     */
    public function businesses(): BelongsToMany
    {
        return $this->belongsToMany(Business::class)
            ->withPivot('notes', 'difficulty_rating', 'last_seen_at')
            ->withTimestamps();
    }

    /**
     * @return HasMany<BusinessPet, $this>
     */
    public function businessNotes(): HasMany
    {
        return $this->hasMany(BusinessPet::class);
    }
}
