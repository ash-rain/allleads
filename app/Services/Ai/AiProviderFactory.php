<?php

namespace App\Services\Ai;

use App\Models\AiSetting;

class AiProviderFactory
{
    /**
     * Resolve an AI provider instance from the given settings.
     *
     * @throws \InvalidArgumentException  When the provider name is not recognised.
     */
    public static function make(AiSetting $setting): AiProviderInterface
    {
        return match ($setting->provider) {
            'openrouter' => new OpenRouterProvider(),
            'groq'       => new GroqProvider(),
            'gemini'     => new GeminiProvider(),
            default      => throw new \InvalidArgumentException(
                "Unknown AI provider: [{$setting->provider}]"
            ),
        };
    }

    /**
     * Make a provider from a raw string identifier.
     * Zero-dependency shortcut used in tests and the settings page.
     */
    public static function makeFromName(string $provider): AiProviderInterface
    {
        return match ($provider) {
            'openrouter' => new OpenRouterProvider(),
            'groq'       => new GroqProvider(),
            'gemini'     => new GeminiProvider(),
            default      => throw new \InvalidArgumentException(
                "Unknown AI provider: [{$provider}]"
            ),
        };
    }
}
