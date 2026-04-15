<?php

namespace App\Ai\Tools\Trend;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class SearchReddit implements Tool
{
    private const USER_AGENT = 'allleads-bot/1.0 (research tool)';

    public function description(): string
    {
        return 'Search Reddit for posts related to a topic. Returns the most relevant posts including title, subreddit, score, comment count, and URLs.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'topic' => $schema
                ->string()
                ->description('The topic or keyword to search for on Reddit.')
                ->required(),
            'days' => $schema
                ->integer()
                ->description('Number of days to look back for posts.')
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
                ->get('https://www.reddit.com/search.json', [
                    'q' => $topic,
                    't' => 'month',
                    'sort' => 'relevance',
                    'limit' => 25,
                    'type' => 'link',
                ]);

            $posts = $this->parseReddit($response);
        } catch (\Throwable $e) {
            Log::warning('SearchReddit tool: request failed', ['error' => $e->getMessage()]);
            $posts = [];
        }

        return json_encode($posts);
    }

    private function parseReddit(mixed $response): array
    {
        try {
            if (! $response || $response->failed()) {
                return [];
            }

            $posts = $response->json('data.children') ?? [];

            return collect($posts)
                ->map(fn ($post) => [
                    'title' => $post['data']['title'] ?? '',
                    'subreddit' => $post['data']['subreddit'] ?? '',
                    'score' => $post['data']['score'] ?? 0,
                    'comments' => $post['data']['num_comments'] ?? 0,
                    'url' => $post['data']['url'] ?? '',
                    'permalink' => 'https://reddit.com'.($post['data']['permalink'] ?? ''),
                    'created_at' => isset($post['data']['created_utc'])
                        ? date('Y-m-d', (int) $post['data']['created_utc'])
                        : null,
                ])
                ->filter(fn ($post) => ! empty($post['title']))
                ->values()
                ->take(20)
                ->toArray();
        } catch (\Throwable $e) {
            Log::warning('SearchReddit tool: parse failed', ['error' => $e->getMessage()]);

            return [];
        }
    }
}
