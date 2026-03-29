<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmailCampaign extends Model
{
    protected $fillable = [
        'name',
        'status',
        'provider',
        'model',
        'style_settings',
        'lead_count',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'style_settings' => 'array',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function drafts(): HasMany
    {
        return $this->hasMany(EmailDraft::class, 'campaign_id');
    }
}
