<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\Http;

/**
 * Google Gemini uses a different API format (not OpenAI-compatible).
 * generateContent endpoint: POST /v1beta/models/{model}:generateContent
 */
class GeminiProvider implements AiProviderInterface
{
    private string $endpoint;

    private string $apiKey;

    private string $defaultModel;

    public function __construct()
    {
        $this->endpoint = config('ai.gemini.endpoint', 'https://generativelanguage.googleapis.com/v1beta');
        $this->apiKey = (string) config('services.gemini.key', '');
        $this->defaultModel = config('ai.gemini.default_model', 'gemini-2.0-flash-lite');
    }

    public function complete(string $systemPrompt, string $userPrompt, array $options = []): string
    {
        $model = $options['model'] ?? $this->defaultModel;
        $temperature = $options['temperature'] ?? 0.7;
        $maxTokens = $options['max_tokens'] ?? 1024;

        $url = "{$this->endpoint}/models/{$model}:generateContent?key={$this->apiKey}";

        $response = Http::timeout(60)->post($url, [
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

    /** Gemini has no public model-list endpoint — return static config. */
    public function availableModels(): array
    {
        return config('ai.gemini.models', []);
    }
}
