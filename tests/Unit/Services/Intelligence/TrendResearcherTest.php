<?php

use App\Services\Intelligence\TrendResearcher;
use Illuminate\Support\Facades\Http;

it('returns structured research data from APIs', function (): void {
    Http::fake([
        'www.reddit.com/*' => Http::response([
            'data' => [
                'children' => [
                    ['data' => ['title' => 'Top restaurant trend', 'subreddit' => 'restaurants', 'score' => 200, 'num_comments' => 30, 'url' => 'https://example.com', 'permalink' => '/r/restaurants/1', 'created_utc' => now()->timestamp]],
                ],
            ],
        ], 200),
        'hn.algolia.com/*' => Http::response([
            'hits' => [
                ['title' => 'HN Story about restaurants', 'url' => 'https://hn.example.com', 'objectID' => '123', 'points' => 150, 'num_comments' => 40, 'created_at' => now()->toIso8601String()],
            ],
        ], 200),
        'gamma-api.polymarket.com/*' => Http::response([
            ['title' => 'Will restaurant tech revenue double by 2026?', 'volume' => '50000', 'liquidity' => '10000', 'endDate' => '2026-01-01', 'markets' => [['id' => 1]]],
        ], 200),
    ]);

    $researcher = new TrendResearcher;
    $result = $researcher->research('restaurant technology', 30);

    expect($result)->toHaveKeys(['reddit', 'hackernews', 'polymarket', 'meta'])
        ->and($result['reddit'])->toHaveCount(1)
        ->and($result['reddit'][0]['title'])->toBe('Top restaurant trend')
        ->and($result['reddit'][0]['subreddit'])->toBe('restaurants')
        ->and($result['hackernews'])->toHaveCount(1)
        ->and($result['hackernews'][0]['title'])->toBe('HN Story about restaurants')
        ->and($result['polymarket'])->toHaveCount(1)
        ->and($result['polymarket'][0]['title'])->toBe('Will restaurant tech revenue double by 2026?')
        ->and($result['meta']['topic'])->toBe('restaurant technology')
        ->and($result['meta']['days'])->toBe(30);
});

it('returns empty arrays on API failures', function (): void {
    Http::fake([
        'www.reddit.com/*' => Http::response(null, 500),
        'hn.algolia.com/*' => Http::response(null, 503),
        'gamma-api.polymarket.com/*' => Http::response(null, 500),
    ]);

    $researcher = new TrendResearcher;
    $result = $researcher->research('failing topic', 30);

    expect($result['reddit'])->toBeArray()->toBeEmpty()
        ->and($result['hackernews'])->toBeArray()->toBeEmpty()
        ->and($result['polymarket'])->toBeArray()->toBeEmpty()
        ->and($result['meta']['topic'])->toBe('failing topic');
});

it('limits reddit results to 20 items', function (): void {
    $posts = [];
    for ($i = 0; $i < 25; $i++) {
        $posts[] = ['data' => ['title' => "Post {$i}", 'subreddit' => 'test', 'score' => 10, 'num_comments' => 2, 'url' => 'https://example.com', 'permalink' => "/r/test/{$i}", 'created_utc' => now()->timestamp]];
    }

    Http::fake([
        'www.reddit.com/*' => Http::response(['data' => ['children' => $posts]], 200),
        'hn.algolia.com/*' => Http::response(['hits' => []], 200),
        'gamma-api.polymarket.com/*' => Http::response([], 200),
    ]);

    $researcher = new TrendResearcher;
    $result = $researcher->research('test', 30);

    expect($result['reddit'])->toHaveCount(20);
});
