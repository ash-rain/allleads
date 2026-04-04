<?php

namespace App\Services\Ai;

class GroqProvider extends AbstractOpenAiCompatibleProvider
{
    protected function endpoint(): string
    {
        return config('ai.groq.endpoint', 'https://api.groq.com/openai/v1');
    }

    protected function apiKey(): string
    {
        return $this->apiKeyOverride ?? (string) config('ai.groq.api_key', '');
    }

    protected function defaultModel(): string
    {
        return config('ai.groq.default_model', 'llama-3.3-70b-versatile');
    }

    protected function configFallbackModels(): array
    {
        return config('ai.groq.models', []);
    }
}
