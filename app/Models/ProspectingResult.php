<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProspectingResult extends Model
{
    use HasFactory;

    public const STATUS_NEW = 'new';

    public const STATUS_SELECTED = 'selected';

    public const STATUS_DISMISSED = 'dismissed';

    public const STATUS_IMPORTED = 'imported';

    public const STATUS_DUPLICATE = 'duplicate';

    protected $fillable = [
        'prospecting_session_id',
        'source',
        'source_id',
        'title',
        'category',
        'address',
        'latitude',
        'longitude',
        'phone',
        'website',
        'email',
        'review_rating',
        'review_count',
        'signals',
        'raw_data',
        'status',
        'lead_id',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'review_rating' => 'decimal:1',
            'signals' => 'array',
            'raw_data' => 'array',
        ];
    }

    // ─── Relationships ──────────────────────────────────────────────────────

    public function session(): BelongsTo
    {
        return $this->belongsTo(ProspectingSession::class, 'prospecting_session_id');
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    // ─── Helpers ────────────────────────────────────────────────────────────

    public function isImported(): bool
    {
        return $this->status === self::STATUS_IMPORTED;
    }

    public function isDismissed(): bool
    {
        return $this->status === self::STATUS_DISMISSED;
    }

    /** Get human-readable signal descriptions. */
    public function signalDescriptions(): array
    {
        return collect($this->signals ?? [])->map(function (string $signal): string {
            return match ($signal) {
                'no_website' => 'No website',
                'no_https' => 'No HTTPS',
                'low_reviews' => 'Low reviews (<20)',
                'low_rating' => 'Low rating (<3.5)',
                'already_a_lead' => 'Already a lead',
                'new_business' => 'New business',
                default => $signal,
            };
        })->all();
    }
}
