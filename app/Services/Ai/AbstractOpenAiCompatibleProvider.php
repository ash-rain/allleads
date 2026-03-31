<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Shared HTTP + caching helpers for OpenAI-compatible endpoints.
 */
abstract class AbstractOpenAiCompatibleProvider implements AiProviderInterface
{
    abstract protected function endpoint(): string;

    abstract protected function apiKey(): string;

    abstract protected function defaultModel(): string;

    abstract protected function configFallbackModels(): array;

    protected function modelsEndpoint(): ?string
    {
        return $this->endpoint().'/models';
    }

    protected function cacheKey(): string
    {
        return 'ai_models_'.static::class;
    }

    protected int $modelsCacheTtl;

    public function __construct()
    {
        $this->modelsCacheTtl = (int) config('ai.models_cache_ttl', 3600);
    }

    // ─── AiProviderInterface ──────────────────────────────────────────────────

    public function complete(string $systemPrompt, string $userPrompt, array $options = []): string
    {
        $requestedModel = ($options['model'] ?? '') ?: $this->defaultModel();
        $temperature = $options['temperature'] ?? 0.7;
        $maxTokens = $options['max_tokens'] ?? 1024;
        $timeout = $options['timeout'] ?? 60;

        // Build an ordered list: requested model first, then any fallback models not yet tried.
        $fallbacks = array_values(array_filter(
            $this->configFallbackModels(),
            fn (string $m) => $m !== $requestedModel,
        ));
        $modelsToTry = [$requestedModel, ...$fallbacks];

        foreach ($modelsToTry as $model) {
            $response = Http::withToken($this->apiKey())
                ->timeout($timeout)
                ->post($this->endpoint().'/chat/completions', [
                    'model' => $model,
                    'temperature' => $temperature,
                    'max_tokens' => $maxTokens,
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user',   'content' => $userPrompt],
                    ],
                ]);

            if ($response->status() === 429) {
                Log::warning(sprintf('[%s] Model %s is rate-limited, trying next.', static::class, $model));

                continue;
            }

            if (! $response->successful()) {
                throw new AiProviderException(
                    sprintf('[%s] API error %d: %s', static::class, $response->status(), $response->body())
                );
            }

            $content = $response->json('choices.0.message.content');

            if (! is_string($content)) {
                throw new AiProviderException(sprintf(
                    '[%s] Unexpected response format. Body: %s',
                    static::class,
                    mb_substr($response->body(), 0, 500)
                ));
            }

            return trim($content);
        }

        throw new RateLimitException(sprintf('[%s] All models are rate-limited.', static::class));
    }

    public function availableModels(): array
    {
        $key = $this->cacheKey();

        return Cache::remember($key, $this->modelsCacheTtl, function () {
            try {
                return $this->fetchModels();
            } catch (\Throwable) {
                return $this->configFallbackModels();
            }
        });
    }

    /** @return string[] */
    protected function fetchModels(): array
    {
        $endpoint = $this->modelsEndpoint();
        if ($endpoint === null) {
            return $this->configFallbackModels();
        }

        $response = Http::withToken($this->apiKey())
            ->timeout(10)
            ->get($endpoint);

        if (! $response->successful()) {
            throw new AiProviderException('Could not fetch model list.');
        }

        $data = $response->json('data', []);

        return array_values(array_map(
            fn (array $m) => $m['id'],
            $data
        ));
    }
}
