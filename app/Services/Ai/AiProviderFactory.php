<?php

namespace App\Services\Ai;

use App\Models\AiSetting;

class AiProviderFactory
{
    /**
     * Resolve an AI provider instance from the given settings.
     *
     * The API key stored on the settings record takes priority; falls back to
     * the value in config (env) so existing deployments keep working.
     *
     * @throws \InvalidArgumentException When the provider name is not recognised.
     */
    public static function make(AiSetting $setting): AiProviderInterface
    {
        $key = $setting->apiKeyFor($setting->provider) ?: null;

        return match ($setting->provider) {
            'openrouter' => new OpenRouterProvider($key),
            'groq' => new GroqProvider($key),
            'gemini' => new GeminiProvider($key),
            default => throw new \InvalidArgumentException(
                "Unknown AI provider: [{$setting->provider}]"
            ),
        };
    }

    /**
     * Make a provider from a raw string identifier.
     * Zero-dependency shortcut used in tests and the settings page.
     *
     * When $apiKey is null the provider will fall back to config/env.
     */
    public static function makeFromName(string $provider, ?string $apiKey = null): AiProviderInterface
    {
        return match ($provider) {
            'openrouter' => new OpenRouterProvider($apiKey),
            'groq' => new GroqProvider($apiKey),
            'gemini' => new GeminiProvider($apiKey),
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
     * configured API key (either in DB or config), so only real alternatives are included.
     */
    public static function makeWithFallback(AiSetting $setting): AiProviderInterface
    {
        $primary = self::make($setting);

        $providers = [$primary];

        foreach (['openrouter', 'groq', 'gemini'] as $name) {
            if ($name === $setting->provider) {
                continue;
            }

            $key = $setting->apiKeyFor($name) ?: (string) config("ai.{$name}.api_key");

            if (empty($key)) {
                continue;
            }

            $providers[] = self::makeFromName($name, $key);
        }

        return new FallbackAiProvider($providers);
    }
}
