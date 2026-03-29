<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserEmailSetting extends Model
{
    protected $fillable = [
        'user_id',
        'sender_name',
        'sender_email',
        'reply_to',
        'default_cc',
        'default_bcc',
        'header_image_path',
        'signature',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
