<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmailDraft extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'lead_id',
        'campaign_id',
        'thread_id',
        'subject',
        'body',
        'status',
        'send_at',
        'error',
        'version',
    ];

    protected function casts(): array
    {
        return [
            'send_at' => 'datetime',
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(EmailCampaign::class);
    }

    public function thread(): BelongsTo
    {
        return $this->belongsTo(EmailThread::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(EmailDraftVersion::class, 'draft_id')->orderByDesc('version');
    }

    public function saveVersion(?int $userId = null): EmailDraftVersion
    {
        $this->increment('version');

        return $this->versions()->create([
            'version'    => $this->version,
            'subject'    => $this->subject,
            'body'       => $this->body,
            'created_by' => $userId,
            'created_at' => now(),
        ]);
    }

    public function restoreVersion(int $versionNumber): void
    {
        $version = $this->versions()->where('version', $versionNumber)->firstOrFail();
        $this->subject = $version->subject;
        $this->body    = $version->body;
        $this->save();
    }
}
