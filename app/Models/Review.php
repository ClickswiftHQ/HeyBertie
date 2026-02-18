<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    /** @use HasFactory<\Database\Factories\ReviewFactory> */
    use HasFactory;

    protected $fillable = [
        'business_id',
        'booking_id',
        'user_id',
        'rating',
        'review_text',
        'photos',
        'is_verified',
        'is_published',
        'response_text',
        'responded_by_user_id',
        'responded_at',
        'is_flagged',
        'flag_reason',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'photos' => 'array',
            'responded_at' => 'datetime',
            'is_verified' => 'boolean',
            'is_published' => 'boolean',
            'is_flagged' => 'boolean',
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
     * @return BelongsTo<Booking, $this>
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function respondedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responded_by_user_id');
    }

    /**
     * @param  Builder<Review>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('is_published', true);
    }

    /**
     * @param  Builder<Review>  $query
     */
    public function scopeVerified(Builder $query): void
    {
        $query->where('is_verified', true);
    }

    /**
     * @param  Builder<Review>  $query
     */
    public function scopeRating(Builder $query, int $stars): void
    {
        $query->where('rating', $stars);
    }

    public function respond(string $text, User $responder): void
    {
        $this->update([
            'response_text' => $text,
            'responded_by_user_id' => $responder->id,
            'responded_at' => now(),
        ]);
    }

    public function flag(string $reason): void
    {
        $this->update([
            'is_flagged' => true,
            'flag_reason' => $reason,
        ]);
    }
}
