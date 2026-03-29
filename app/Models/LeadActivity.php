<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadActivity extends Model
{
    public $timestamps = false; // only created_at

    protected $fillable = [
        'lead_id',
        'event',
        'payload',
        'created_by',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'payload'    => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function record(Lead $lead, string $event, array $payload = [], ?int $userId = null): self
    {
        return self::create([
            'lead_id'    => $lead->id,
            'event'      => $event,
            'payload'    => $payload,
            'created_by' => $userId,
            'created_at' => now(),
        ]);
    }
}
