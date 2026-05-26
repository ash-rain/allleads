<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ProspectingSession extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_SEARCHING = 'searching';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'business_id',
        'territory_id',
        'uuid',
        'search_query',
        'filters',
        'status',
        'sources_used',
        'result_count',
        'imported_count',
        'dismissed_count',
        'searched_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'filters' => 'array',
            'sources_used' => 'array',
            'searched_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $session) {
            if (empty($session->uuid)) {
                $session->uuid = (string) Str::uuid();
            }
        });
    }

    // ─── Relationships ──────────────────────────────────────────────────────

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function territory(): BelongsTo
    {
        return $this->belongsTo(Territory::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return HasMany<ProspectingResult, $this> */
    public function results(): HasMany
    {
        return $this->hasMany(ProspectingResult::class);
    }

    // ─── Helpers ────────────────────────────────────────────────────────────

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isSearching(): bool
    {
        return $this->status === self::STATUS_SEARCHING;
    }

    /** Recalculate counts from results. */
    public function refreshCounts(): void
    {
        $this->update([
            'result_count' => $this->results()->count(),
            'imported_count' => $this->results()->where('status', 'imported')->count(),
            'dismissed_count' => $this->results()->where('status', 'dismissed')->count(),
        ]);
    }
}
