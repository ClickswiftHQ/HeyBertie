<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $business_id
 * @property string $document_type
 * @property string $file_path
 * @property string $original_filename
 * @property string $mime_type
 * @property int $file_size
 * @property string $status
 * @property string|null $reviewer_notes
 * @property int|null $reviewed_by_user_id
 * @property \Carbon\CarbonImmutable|null $reviewed_at
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\Business $business
 * @property-read \App\Models\User|null $reviewedBy
 *
 * @method static Builder<static>|VerificationDocument approved()
 * @method static \Database\Factories\VerificationDocumentFactory factory($count = null, $state = [])
 * @method static Builder<static>|VerificationDocument newModelQuery()
 * @method static Builder<static>|VerificationDocument newQuery()
 * @method static Builder<static>|VerificationDocument ofType(string $type)
 * @method static Builder<static>|VerificationDocument pending()
 * @method static Builder<static>|VerificationDocument query()
 * @method static Builder<static>|VerificationDocument rejected()
 * @method static Builder<static>|VerificationDocument whereBusinessId($value)
 * @method static Builder<static>|VerificationDocument whereCreatedAt($value)
 * @method static Builder<static>|VerificationDocument whereDocumentType($value)
 * @method static Builder<static>|VerificationDocument whereFilePath($value)
 * @method static Builder<static>|VerificationDocument whereFileSize($value)
 * @method static Builder<static>|VerificationDocument whereId($value)
 * @method static Builder<static>|VerificationDocument whereMimeType($value)
 * @method static Builder<static>|VerificationDocument whereOriginalFilename($value)
 * @method static Builder<static>|VerificationDocument whereReviewedAt($value)
 * @method static Builder<static>|VerificationDocument whereReviewedByUserId($value)
 * @method static Builder<static>|VerificationDocument whereReviewerNotes($value)
 * @method static Builder<static>|VerificationDocument whereStatus($value)
 * @method static Builder<static>|VerificationDocument whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class VerificationDocument extends Model
{
    /** @use HasFactory<\Database\Factories\VerificationDocumentFactory> */
    use HasFactory;

    protected $fillable = [
        'business_id',
        'document_type',
        'file_path',
        'original_filename',
        'mime_type',
        'file_size',
        'status',
        'reviewer_notes',
        'reviewed_by_user_id',
        'reviewed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
            'file_size' => 'integer',
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
    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    /**
     * @param  Builder<VerificationDocument>  $query
     */
    public function scopePending(Builder $query): void
    {
        $query->where('status', 'pending');
    }

    /**
     * @param  Builder<VerificationDocument>  $query
     */
    public function scopeApproved(Builder $query): void
    {
        $query->where('status', 'approved');
    }

    /**
     * @param  Builder<VerificationDocument>  $query
     */
    public function scopeRejected(Builder $query): void
    {
        $query->where('status', 'rejected');
    }

    /**
     * @param  Builder<VerificationDocument>  $query
     */
    public function scopeOfType(Builder $query, string $type): void
    {
        $query->where('document_type', $type);
    }
}
