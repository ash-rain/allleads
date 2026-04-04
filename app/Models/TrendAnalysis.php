<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $status
 * @property array<string, mixed>|null $raw_data
 * @property array<string, mixed>|null $result
 */
class TrendAnalysis extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'user_id',
        'topic',
        'status',
        'raw_data',
        'result',
        'provider',
        'model',
        'error_message',
        'started_at',
        'completed_at',
        'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'raw_data' => 'array',
            'result' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
