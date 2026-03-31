<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\Log;

/**
 * Wraps an ordered list of providers and transparently retries on rate limits.
 *
 * On RateLimitException the next provider in the chain is tried; all other
 * exceptions propagate immediately. If every provider is exhausted a
 * RateLimitException is re-thrown with its $previous chain intact.
 */
class FallbackAiProvider implements AiProviderInterface
{
    /**
     * @param  AiProviderInterface[]  $providers  Ordered list; first entry is the primary provider.
     */
    public function __construct(private readonly array $providers) {}

    public function complete(string $systemPrompt, string $userPrompt, array $options = []): string
    {
        $lastException = null;

        foreach ($this->providers as $provider) {
            try {
                return $provider->complete($systemPrompt, $userPrompt, $options);
            } catch (RateLimitException $e) {
                Log::warning(sprintf(
                    '[FallbackAiProvider] %s exhausted all models, trying next provider.',
                    $provider::class,
                ));
                $lastException = $e;
            }
        }

        throw new RateLimitException('All AI providers are rate-limited.', previous: $lastException);
    }

    public function availableModels(): array
    {
        return $this->providers[0]->availableModels();
    }
}
