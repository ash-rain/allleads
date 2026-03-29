<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImportBatch extends Model
{
    protected $fillable = [
        'uuid',
        'filename',
        'status',
        'progress',
        'total',
        'created_count',
        'updated_count',
        'skipped_count',
        'failed_count',
        'created_by',
        'undone_at',
    ];

    protected function casts(): array
    {
        return [
            'undone_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    public function isUndone(): bool
    {
        return $this->undone_at !== null;
    }
}
