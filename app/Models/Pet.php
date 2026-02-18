<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
}
