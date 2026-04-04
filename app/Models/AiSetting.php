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
        'include_cta',
        'include_ps',
        'custom_system_prompt',
        'temperature',
        'max_tokens',
        'timeout',
        'openrouter_api_key',
        'groq_api_key',
        'gemini_api_key',
    ];

    protected function casts(): array
    {
        return [
            'include_cta' => 'boolean',
            'include_ps' => 'boolean',
            'temperature' => 'decimal:2',
            'openrouter_api_key' => 'encrypted',
            'groq_api_key' => 'encrypted',
            'gemini_api_key' => 'encrypted',
        ];
    }

    /** Return the stored API key for the given provider, or empty string if not set. */
    public function apiKeyFor(string $provider): string
    {
        return (string) match ($provider) {
            'openrouter' => $this->openrouter_api_key,
            'groq' => $this->groq_api_key,
            'gemini' => $this->gemini_api_key,
            default => '',
        };
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
