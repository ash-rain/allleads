<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Google Gemini uses a different API format (not OpenAI-compatible).
 * generateContent endpoint: POST /v1beta/models/{model}:generateContent
 */
class GeminiProvider implements AiProviderInterface
{
    private string $endpoint;

    private string $apiKey;

    private string $defaultModel;

    public function __construct(?string $apiKey = null)
    {
        $this->endpoint = config('ai.gemini.endpoint', 'https://generativelanguage.googleapis.com/v1beta');
        $this->apiKey = $apiKey ?: (string) config('ai.gemini.api_key', '');
        $this->defaultModel = config('ai.gemini.default_model', 'gemini-2.0-flash-lite');
    }

    public function complete(string $systemPrompt, string $userPrompt, array $options = []): string
    {
        $requestedModel = $options['model'] ?? $this->defaultModel;
        $temperature = $options['temperature'] ?? 0.7;
        $maxTokens = $options['max_tokens'] ?? 1024;
        $timeout = $options['timeout'] ?? 60;

        // Build an ordered list: requested model first, then any fallback models not yet tried.
        $allModels = config('ai.gemini.models', []);
        $fallbacks = array_values(array_filter($allModels, fn (string $m) => $m !== $requestedModel));
        $modelsToTry = [$requestedModel, ...$fallbacks];

        foreach ($modelsToTry as $model) {
            $url = "{$this->endpoint}/models/{$model}:generateContent?key={$this->apiKey}";

            $response = Http::timeout($timeout)->post($url, [
                'system_instruction' => [
                    'parts' => [['text' => $systemPrompt]],
                ],
                'contents' => [
                    ['role' => 'user', 'parts' => [['text' => $userPrompt]]],
                ],
                'generationConfig' => [
                    'temperature' => $temperature,
                    'maxOutputTokens' => $maxTokens,
                ],
            ]);

            if ($response->status() === 429) {
                Log::warning(sprintf('[GeminiProvider] Model %s is rate-limited, trying next.', $model));

                continue;
            }

            if (! $response->successful()) {
                throw new AiProviderException(
                    sprintf('[GeminiProvider] API error %d: %s', $response->status(), $response->body())
                );
            }

            $text = $response->json('candidates.0.content.parts.0.text');

            if (! is_string($text)) {
                throw new AiProviderException('[GeminiProvider] Unexpected response format.');
            }

            return trim($text);
        }

        throw new RateLimitException('[GeminiProvider] All models are rate-limited.');
    }

    /** Gemini has no public model-list endpoint — return static config. */
    public function availableModels(): array
    {
        return config('ai.gemini.models', []);
    }
}
