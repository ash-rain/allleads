<?php

namespace App\Ai\Tools\Trend;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class SearchHackerNews implements Tool
{
    private const USER_AGENT = 'allleads-bot/1.0 (research tool)';

    public function description(): string
    {
        return 'Search Hacker News stories related to a topic. Returns the most relevant stories including title, URL, points, comment count, and publication date.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'topic' => $schema
                ->string()
                ->description('The topic or keyword to search for on Hacker News.')
                ->required(),
            'days' => $schema
                ->integer()
                ->description('Number of days to look back for stories.')
                ->default(30),
        ];
    }

    public function handle(Request $request): string
    {
        $topic = $request->get('topic');
        $days = (int) $request->get('days', 30);

        try {
            $response = Http::timeout(10)
                ->withHeaders(['User-Agent' => self::USER_AGENT])
                ->get('https://hn.algolia.com/api/v1/search', [
                    'query' => $topic,
                    'tags' => 'story',
                    'numericFilters' => 'created_at_i>'.now()->subDays($days)->timestamp,
                    'hitsPerPage' => 25,
                ]);

            $stories = $this->parseHackerNews($response);
        } catch (\Throwable $e) {
            Log::warning('SearchHackerNews tool: request failed', ['error' => $e->getMessage()]);
            $stories = [];
        }

        return json_encode($stories);
    }

    private function parseHackerNews(mixed $response): array
    {
        try {
            if (! $response || $response->failed()) {
                return [];
            }

            $hits = $response->json('hits') ?? [];

            return collect($hits)
                ->map(fn ($hit) => [
                    'title' => $hit['title'] ?? $hit['story_title'] ?? '',
                    'url' => $hit['url'] ?? $hit['story_url'] ?? '',
                    'hn_url' => 'https://news.ycombinator.com/item?id='.($hit['objectID'] ?? ''),
                    'points' => $hit['points'] ?? 0,
                    'comments' => $hit['num_comments'] ?? 0,
                    'created_at' => isset($hit['created_at']) ? substr($hit['created_at'], 0, 10) : null,
                ])
                ->filter(fn ($hit) => ! empty($hit['title']))
                ->values()
                ->take(20)
                ->toArray();
        } catch (\Throwable $e) {
            Log::warning('SearchHackerNews tool: parse failed', ['error' => $e->getMessage()]);

            return [];
        }
    }
}
