<?php

namespace App\Services\Ai;

interface AiProviderInterface
{
    /**
     * Generate a completion from the given prompts.
     *
     * @param  string  $systemPrompt  The system / instruction part.
     * @param  string  $userPrompt  The user / content part.
     * @param  array<string, mixed>  $options  Optional overrides (temperature, max_tokens, model).
     * @return string The generated text.
     *
     * @throws AiProviderException On API errors or parse failures.
     */
    public function complete(string $systemPrompt, string $userPrompt, array $options = []): string;

    /**
     * Return the list of available model IDs for this provider.
     * Results may be cached. Falls back to config on API error.
     *
     * @return string[]
     */
    public function availableModels(): array;
}
