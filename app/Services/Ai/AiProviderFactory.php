<?php

namespace App\Services\Ai;

use App\Models\AiSetting;

class AiProviderFactory
{
    /**
     * Resolve an AI provider instance from the given settings.
     *
     * @throws \InvalidArgumentException When the provider name is not recognised.
     */
    public static function make(AiSetting $setting): AiProviderInterface
    {
        return match ($setting->provider) {
            'openrouter' => new OpenRouterProvider,
            'groq' => new GroqProvider,
            'gemini' => new GeminiProvider,
            default => throw new \InvalidArgumentException(
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
            'openrouter' => new OpenRouterProvider,
            'groq' => new GroqProvider,
            'gemini' => new GeminiProvider,
            default => throw new \InvalidArgumentException(
                "Unknown AI provider: [{$provider}]"
            ),
        };
    }

    /**
     * Build a provider chain that automatically falls back to other providers on rate limits.
     *
     * The primary provider is taken from $setting. Remaining providers are appended in the
     * fixed order (openrouter → groq → gemini), skipping the primary and any without a
     * configured API key, so only real alternatives are included.
     */
    public static function makeWithFallback(AiSetting $setting): AiProviderInterface
    {
        $primary = self::make($setting);

        $providers = [$primary];

        foreach (['openrouter', 'groq', 'gemini'] as $name) {
            if ($name === $setting->provider) {
                continue;
            }

            if (empty(config("ai.{$name}.api_key"))) {
                continue;
            }

            $providers[] = self::makeFromName($name);
        }

        return new FallbackAiProvider($providers);
    }
}
