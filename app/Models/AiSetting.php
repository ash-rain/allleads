<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider',
        'model',
        'language',
        'tone',
        'length',
        'personalisation',
        'opener_style',
        'include_portfolio',
        'include_audit',
        'include_cta',
        'include_ps',
        'custom_system_prompt',
        'temperature',
        'max_tokens',
        'timeout',
    ];

    protected function casts(): array
    {
        return [
            'include_portfolio' => 'boolean',
            'include_audit' => 'boolean',
            'include_cta' => 'boolean',
            'include_ps' => 'boolean',
            'temperature' => 'decimal:2',
        ];
    }

    /** Retrieve the singleton row, creating defaults if absent. */
    public static function singleton(): self
    {
        $provider = config('ai.default', 'openrouter');

        return self::firstOrCreate([], [
            'provider' => $provider,
            'model' => config("ai.{$provider}.default_model"),
            'language' => 'Bulgarian',
            'tone' => 'professional',
            'length' => 'medium',
            'personalisation' => 'medium',
            'opener_style' => 'question',
        ]);
    }
}
