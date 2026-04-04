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
        return $this->apiKeyOverride ?? (string) config('ai.openrouter.api_key', '');
    }

    protected function defaultModel(): string
    {
        return config('ai.openrouter.default_model', 'nvidia/nemotron-3-super-120b-a12b:free');
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
