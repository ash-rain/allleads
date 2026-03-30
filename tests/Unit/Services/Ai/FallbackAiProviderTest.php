<?php

use App\Services\Ai\AiProviderInterface;
use App\Services\Ai\FallbackAiProvider;
use App\Services\Ai\RateLimitException;

// ─── Helpers ─────────────────────────────────────────────────────────────────

/**
 * Build an AiProviderInterface stub that always returns the given string.
 */
function successProvider(string $response = 'ok'): AiProviderInterface
{
    return new class($response) implements AiProviderInterface
    {
        public function __construct(private string $r) {}

        public function complete(string $s, string $u, array $o = []): string
        {
            return $this->r;
        }

        public function availableModels(): array
        {
            return [];
        }
    };
}

/**
 * Build an AiProviderInterface stub that always throws RateLimitException.
 */
function rateLimitedProvider(): AiProviderInterface
{
    return new class implements AiProviderInterface
    {
        public function complete(string $s, string $u, array $o = []): string
        {
            throw new RateLimitException('Rate limited.');
        }

        public function availableModels(): array
        {
            return [];
        }
    };
}

// ─── Tests ───────────────────────────────────────────────────────────────────

it('returns the primary provider response when no rate limit occurs', function (): void {
    $provider = new FallbackAiProvider([successProvider('Hello!'), rateLimitedProvider()]);

    expect($provider->complete('system', 'user'))->toBe('Hello!');
});

it('falls back to the next provider when the primary is rate-limited', function (): void {
    $provider = new FallbackAiProvider([rateLimitedProvider(), successProvider('Fallback!')]);

    expect($provider->complete('system', 'user'))->toBe('Fallback!');
});

it('throws RateLimitException when all providers are exhausted', function (): void {
    $provider = new FallbackAiProvider([rateLimitedProvider(), rateLimitedProvider()]);

    expect(fn() => $provider->complete('system', 'user'))
        ->toThrow(RateLimitException::class, 'All AI providers are rate-limited.');
});

it('preserves the last exception as previous when all providers fail', function (): void {
    $provider = new FallbackAiProvider([rateLimitedProvider(), rateLimitedProvider()]);

    try {
        $provider->complete('system', 'user');
        $this->fail('Expected RateLimitException');
    } catch (RateLimitException $e) {
        expect($e->getPrevious())->toBeInstanceOf(RateLimitException::class);
    }
});

it('delegates availableModels to the first provider', function (): void {
    $first = new class implements AiProviderInterface
    {
        public function complete(string $s, string $u, array $o = []): string
        {
            return '';
        }

        public function availableModels(): array
        {
            return ['model-a', 'model-b'];
        }
    };

    $provider = new FallbackAiProvider([$first, successProvider()]);

    expect($provider->availableModels())->toBe(['model-a', 'model-b']);
});
