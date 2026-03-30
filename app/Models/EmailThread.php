<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmailThread extends Model
{
    use HasFactory;

    protected $fillable = ['lead_id', 'thread_key', 'status'];

    /** @return BelongsTo<Lead, $this> */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(EmailMessage::class, 'thread_id')->orderBy('created_at');
    }

    public function drafts(): HasMany
    {
        return $this->hasMany(EmailDraft::class, 'thread_id');
    }
}
