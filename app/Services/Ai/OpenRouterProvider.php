<?php

namespace App\Services\Ai;

class OpenRouterProvider extends AbstractOpenAiCompatibleProvider
{
    protected function endpoint(): string
    {
        return config('ai.openrouter.endpoint', 'https://openrouter.ai/api/v1');
    }

    protected function apiKey(): string
    {
        return (string) config('services.openrouter.key', '');
    }

    protected function defaultModel(): string
    {
        return config('ai.openrouter.default_model', 'mistralai/mistral-7b-instruct:free');
    }

    protected function configFallbackModels(): array
    {
        return config('ai.openrouter.models', []);
    }

    /** OpenRouter: only `:free` suffix models for cost = $0 */
    protected function fetchModels(): array
    {
        $all = parent::fetchModels();

        return array_values(array_filter($all, fn (string $id) => str_ends_with($id, ':free')));
    }
}
