<?php

namespace App\Ai\Tools\Trend;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class SearchPolymarket implements Tool
{
    private const USER_AGENT = 'allleads-bot/1.0 (research tool)';

    public function description(): string
    {
        return 'Search Polymarket prediction markets related to a topic. Returns active markets including title, description, trading volume, liquidity, end date, and market count.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'topic' => $schema
                ->string()
                ->description('The topic or keyword to search for on Polymarket.')
                ->required(),
        ];
    }

    public function handle(Request $request): string
    {
        $topic = $request->get('topic');

        try {
            $response = Http::timeout(10)
                ->withHeaders(['User-Agent' => self::USER_AGENT])
                ->get('https://gamma-api.polymarket.com/events', [
                    'tag' => 'all',
                    'query' => $topic,
                    'active' => 'true',
                    'limit' => 10,
                ]);

            $events = $this->parsePolymarket($response);
        } catch (\Throwable $e) {
            Log::warning('SearchPolymarket tool: request failed', ['error' => $e->getMessage()]);
            $events = [];
        }

        return json_encode($events);
    }

    private function parsePolymarket(mixed $response): array
    {
        try {
            if (! $response || $response->failed()) {
                return [];
            }

            $events = $response->json() ?? [];

            if (! is_array($events)) {
                return [];
            }

            return collect($events)
                ->map(fn ($event) => [
                    'title' => $event['title'] ?? '',
                    'description' => isset($event['description']) ? mb_substr($event['description'], 0, 200) : null,
                    'volume' => $event['volume'] ?? null,
                    'liquidity' => $event['liquidity'] ?? null,
                    'end_date' => $event['endDate'] ?? null,
                    'markets_count' => isset($event['markets']) ? count($event['markets']) : 0,
                ])
                ->filter(fn ($event) => ! empty($event['title']))
                ->values()
                ->take(10)
                ->toArray();
        } catch (\Throwable $e) {
            Log::warning('SearchPolymarket tool: parse failed', ['error' => $e->getMessage()]);

            return [];
        }
    }
}
