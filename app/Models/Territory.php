<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Territory extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'business_id',
        'name',
        'latitude',
        'longitude',
        'radius_km',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'radius_km' => 'decimal:1',
        ];
    }

    // ─── Relationships ──────────────────────────────────────────────────────

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return HasMany<ProspectingSession, $this> */
    public function prospectingSessions(): HasMany
    {
        return $this->hasMany(ProspectingSession::class);
    }

    // ─── Helpers ────────────────────────────────────────────────────────────

    /** Get the display label for this territory (name + radius). */
    public function displayLabel(): string
    {
        return "{$this->name} ({$this->radius_km}km)";
    }
}
