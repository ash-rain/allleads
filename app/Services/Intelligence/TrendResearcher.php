<?php

namespace App\Services\Intelligence;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TrendResearcher
{
    private const USER_AGENT = 'allleads-bot/1.0 (research tool)';

    /**
     * Research a topic across Reddit, Hacker News and Polymarket.
     *
     * @return array<string, mixed>
     */
    public function research(string $topic, int $days = 30): array
    {
        $results = Http::pool(function ($pool) use ($topic, $days): array {
            return [
                'reddit' => $pool->as('reddit')
                    ->timeout(10)
                    ->withHeaders(['User-Agent' => self::USER_AGENT])
                    ->get('https://www.reddit.com/search.json', [
                        'q' => $topic,
                        't' => 'month',
                        'sort' => 'relevance',
                        'limit' => 25,
                        'type' => 'link',
                    ]),

                'hackernews' => $pool->as('hackernews')
                    ->timeout(10)
                    ->withHeaders(['User-Agent' => self::USER_AGENT])
                    ->get('https://hn.algolia.com/api/v1/search', [
                        'query' => $topic,
                        'tags' => 'story',
                        'numericFilters' => 'created_at_i>'.now()->subDays($days)->timestamp,
                        'hitsPerPage' => 25,
                    ]),

                'polymarket' => $pool->as('polymarket')
                    ->timeout(10)
                    ->withHeaders(['User-Agent' => self::USER_AGENT])
                    ->get('https://gamma-api.polymarket.com/events', [
                        'tag' => 'all',
                        'query' => $topic,
                        'active' => 'true',
                        'limit' => 10,
                    ]),

                'news' => $pool->as('news')
                    ->timeout(10)
                    ->withHeaders(['User-Agent' => self::USER_AGENT])
                    ->get('https://news.google.com/rss/search', [
                        'q' => $topic,
                        'hl' => 'en-US',
                        'gl' => 'US',
                        'ceid' => 'US:en',
                    ]),
            ];
        });

        return [
            'reddit' => $this->parseReddit($results['reddit'] ?? null),
            'hackernews' => $this->parseHackerNews($results['hackernews'] ?? null),
            'polymarket' => $this->parsePolymarket($results['polymarket'] ?? null),
            'news' => $this->parseGoogleNews($results['news'] ?? null),
            'meta' => [
                'topic' => $topic,
                'days' => $days,
                'fetched_at' => now()->toIsoString(),
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
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
            Log::warning('TrendResearcher: Reddit parse failed', ['error' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
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
            Log::warning('TrendResearcher: HackerNews parse failed', ['error' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
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
            Log::warning('TrendResearcher: Polymarket parse failed', ['error' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function parseGoogleNews(mixed $response): array
    {
        try {
            if (! $response || $response->failed()) {
                return [];
            }

            $xml = simplexml_load_string($response->body());

            if (! $xml || ! isset($xml->channel->item)) {
                return [];
            }

            $results = [];

            foreach ($xml->channel->item as $item) {
                $title = (string) $item->title;
                $source = (string) ($item->source ?? '');

                if (empty($title)) {
                    continue;
                }

                // Google News appends " - Source Name" to titles — strip it to avoid duplication
                if ($source && str_ends_with($title, ' - '.$source)) {
                    $title = substr($title, 0, -strlen(' - '.$source));
                }

                $results[] = [
                    'title' => $title,
                    'url' => (string) $item->link,
                    'source' => $source,
                    'published_at' => ! empty($item->pubDate)
                        ? date('Y-m-d', strtotime((string) $item->pubDate))
                        : null,
                ];

                if (count($results) >= 20) {
                    break;
                }
            }

            return $results;
        } catch (\Throwable $e) {
            Log::warning('TrendResearcher: Google News parse failed', ['error' => $e->getMessage()]);

            return [];
        }
    }
}
