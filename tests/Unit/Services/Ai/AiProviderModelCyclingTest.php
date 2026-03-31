<?php

use App\Services\Ai\AbstractOpenAiCompatibleProvider;
use App\Services\Ai\AiProviderException;
use App\Services\Ai\RateLimitException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

// ─── Helpers ─────────────────────────────────────────────────────────────────

/**
 * Build a minimal concrete implementation of the abstract provider for testing.
 *
 * @param  string[]  $fallbackModels
 */
function makeTestProvider(
    string $defaultModel = 'model-a',
    array $fallbackModels = ['model-b', 'model-c'],
): AbstractOpenAiCompatibleProvider {
    return new class($defaultModel, $fallbackModels) extends AbstractOpenAiCompatibleProvider
    {
        public function __construct(private string $dm, private array $fm)
        {
            parent::__construct();
        }

        protected function endpoint(): string
        {
            return 'https://ai.test/v1';
        }

        protected function apiKey(): string
        {
            return 'test-key';
        }

        protected function defaultModel(): string
        {
            return $this->dm;
        }

        protected function configFallbackModels(): array
        {
            return $this->fm;
        }
    };
}

/** Stub a successful chat/completions response for the given model. */
function chatSuccess(string $text = 'Hello!'): array
{
    return ['choices' => [['message' => ['content' => $text]]]];
}

// ─── Tests ───────────────────────────────────────────────────────────────────

it('returns the response when the first model succeeds', function (): void {
    Http::fake(['*/chat/completions' => Http::response(chatSuccess('Great!'), 200)]);

    $result = makeTestProvider()->complete('system', 'user', ['model' => 'model-a']);

    expect($result)->toBe('Great!');
});

it('cycles to the next model on a 429 and returns its response', function (): void {
    Http::fake(function (Request $request) {
        $model = $request->data()['model'] ?? '';

        return $model === 'model-a'
            ? Http::response(['error' => 'rate limited'], 429)
            : Http::response(chatSuccess('Fallback!'), 200);
    });

    $result = makeTestProvider('model-a', ['model-b'])->complete('system', 'user', ['model' => 'model-a']);

    expect($result)->toBe('Fallback!');
});

it('throws RateLimitException when every model is rate-limited', function (): void {
    Http::fake(['*/chat/completions' => Http::response(['error' => 'rate limited'], 429)]);

    expect(fn () => makeTestProvider('model-a', ['model-b'])->complete('system', 'user'))
        ->toThrow(RateLimitException::class);
});

it('throws AiProviderException immediately on a non-429 HTTP error without cycling', function (): void {
    $callCount = 0;

    Http::fake(function () use (&$callCount) {
        $callCount++;

        return Http::response(['error' => 'bad request'], 400);
    });

    expect(fn () => makeTestProvider('model-a', ['model-b'])->complete('system', 'user'))
        ->toThrow(AiProviderException::class);

    // Only one HTTP call should have been made — no cycling on non-429.
    expect($callCount)->toBe(1);
});

it('does not duplicate the requested model in the cycle list', function (): void {
    $calls = [];

    Http::fake(function (Request $request) use (&$calls) {
        $calls[] = $request->data()['model'] ?? '';

        return Http::response(['error' => 'rate limited'], 429);
    });

    expect(fn () => makeTestProvider('model-b', ['model-a', 'model-b', 'model-c'])->complete('system', 'user'))
        ->toThrow(RateLimitException::class);

    // model-b should appear only once even though it appears in both the
    // requested model and the fallback list.
    expect(array_count_values($calls)['model-b'])->toBe(1);
});
