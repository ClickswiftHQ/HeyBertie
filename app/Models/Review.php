<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $business_id
 * @property int|null $booking_id
 * @property int $user_id
 * @property int $rating
 * @property string|null $review_text
 * @property array<array-key, mixed>|null $photos
 * @property bool $is_verified
 * @property bool $is_published
 * @property string|null $response_text
 * @property int|null $responded_by_user_id
 * @property \Carbon\CarbonImmutable|null $responded_at
 * @property bool $is_flagged
 * @property string|null $flag_reason
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\Booking|null $booking
 * @property-read \App\Models\Business $business
 * @property-read \App\Models\User|null $respondedBy
 * @property-read \App\Models\User $user
 *
 * @method static \Database\Factories\ReviewFactory factory($count = null, $state = [])
 * @method static Builder<static>|Review newModelQuery()
 * @method static Builder<static>|Review newQuery()
 * @method static Builder<static>|Review published()
 * @method static Builder<static>|Review query()
 * @method static Builder<static>|Review rating(int $stars)
 * @method static Builder<static>|Review verified()
 * @method static Builder<static>|Review whereBookingId($value)
 * @method static Builder<static>|Review whereBusinessId($value)
 * @method static Builder<static>|Review whereCreatedAt($value)
 * @method static Builder<static>|Review whereFlagReason($value)
 * @method static Builder<static>|Review whereId($value)
 * @method static Builder<static>|Review whereIsFlagged($value)
 * @method static Builder<static>|Review whereIsPublished($value)
 * @method static Builder<static>|Review whereIsVerified($value)
 * @method static Builder<static>|Review wherePhotos($value)
 * @method static Builder<static>|Review whereRating($value)
 * @method static Builder<static>|Review whereRespondedAt($value)
 * @method static Builder<static>|Review whereRespondedByUserId($value)
 * @method static Builder<static>|Review whereResponseText($value)
 * @method static Builder<static>|Review whereReviewText($value)
 * @method static Builder<static>|Review whereUpdatedAt($value)
 * @method static Builder<static>|Review whereUserId($value)
 *
 * @mixin \Eloquent
 */
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
