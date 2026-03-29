<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'thread_id',
        'role',
        'subject',
        'body',
        'message_id',
        'sender',
        'source',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }

    public function thread(): BelongsTo
    {
        return $this->belongsTo(EmailThread::class);
    }

    public function isOutbound(): bool
    {
        return in_array($this->role, ['outbound', 'ai_draft'], true);
    }

    public function isInbound(): bool
    {
        return in_array($this->role, ['lead_reply', 'manual'], true);
    }
}
