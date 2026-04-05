<?php

use App\Services\Intelligence\GeoAnalyzer;
use Illuminate\Support\Facades\Http;

it('returns structured analysis data for a URL', function (): void {
    Http::fake([
        'example.com' => Http::response('<html><head><title>Example</title></head><body><p>We offer great services with 99% uptime. Founded in 2010. Over 500 clients served.</p></body></html>', 200, ['Content-Type' => 'text/html']),
        'example.com/robots.txt' => Http::response("User-agent: *\nDisallow: /admin\n", 200, ['Content-Type' => 'text/plain']),
        'example.com/llms.txt' => Http::response('# LLMs\nThis site is AI-friendly.', 200, ['Content-Type' => 'text/plain']),
        'en.wikipedia.org/*' => Http::response(['pages' => []], 200),
        'www.wikidata.org/*' => Http::response(['search' => []], 200),
    ]);

    $analyzer = new GeoAnalyzer;
    $result = $analyzer->analyze('https://example.com', 'Example Corp');

    expect($result)->toHaveKeys(['page_data', 'robots_txt', 'llms_txt', 'citability', 'brand_mentions', 'schema_markup', 'technical_seo'])
        ->and($result['page_data']['fetched'])->toBeTrue()
        ->and($result['page_data']['url'])->toBe('https://example.com')
        ->and($result['robots_txt']['found'])->toBeTrue()
        ->and($result['llms_txt']['found'])->toBeTrue()
        ->and($result['citability'])->toHaveKeys(['total', 'grade'])
        ->and($result['citability']['total'])->toBeNumeric()
        ->and($result['robots_txt']['ai_crawlers'])->toHaveKey('GPTBot');
});

it('returns empty page_data when URL is unreachable', function (): void {
    Http::fake([
        'broken.example.com' => Http::response(null, 500),
        'broken.example.com/robots.txt' => Http::response(null, 404),
        'broken.example.com/llms.txt' => Http::response(null, 404),
        'en.wikipedia.org/*' => Http::response(['pages' => []], 200),
        'www.wikidata.org/*' => Http::response(['search' => []], 200),
    ]);

    $analyzer = new GeoAnalyzer;
    $result = $analyzer->analyze('https://broken.example.com', 'Broken Corp');

    expect($result['page_data']['fetched'])->toBeFalse()
        ->and($result['page_data']['word_count'])->toBe(0)
        ->and($result['robots_txt']['found'])->toBeFalse()
        ->and($result['llms_txt']['found'])->toBeFalse();
});

it('marks all AI crawlers as unknown when robots.txt is missing', function (): void {
    Http::fake([
        'example.com' => Http::response('<html><body>Content</body></html>', 200, ['Content-Type' => 'text/html']),
        'example.com/robots.txt' => Http::response(null, 404),
        'example.com/llms.txt' => Http::response(null, 404),
        'en.wikipedia.org/*' => Http::response(['pages' => []], 200),
        'www.wikidata.org/*' => Http::response(['search' => []], 200),
    ]);

    $analyzer = new GeoAnalyzer;
    $result = $analyzer->analyze('https://example.com');

    foreach ($result['robots_txt']['ai_crawlers'] as $crawler) {
        expect($crawler['status'])->toBe('unknown');
    }
});

it('detects AI crawler blocking from robots.txt', function (): void {
    Http::fake([
        'example.com' => Http::response('<html><body>Content</body></html>', 200, ['Content-Type' => 'text/html']),
        'example.com/robots.txt' => Http::response("User-agent: GPTBot\nDisallow: /\n\nUser-agent: ClaudeBot\nDisallow: /\n", 200, ['Content-Type' => 'text/plain']),
        'example.com/llms.txt' => Http::response(null, 404),
        'en.wikipedia.org/*' => Http::response(['pages' => []], 200),
        'www.wikidata.org/*' => Http::response(['search' => []], 200),
    ]);

    $analyzer = new GeoAnalyzer;
    $result = $analyzer->analyze('https://example.com');

    expect($result['robots_txt']['ai_crawlers']['GPTBot']['status'])->toBe('blocked')
        ->and($result['robots_txt']['ai_crawlers']['ClaudeBot']['status'])->toBe('blocked');
});

it('analyzes brand without website using brand mentions only', function (): void {
    Http::fake([
        'en.wikipedia.org/*' => Http::response(['pages' => []], 200),
        'www.wikidata.org/*' => Http::response(['search' => []], 200),
    ]);

    $analyzer = new GeoAnalyzer;
    $result = $analyzer->analyzeWithoutWebsite('Test Brand Corp');

    expect($result['page_data']['fetched'])->toBeFalse()
        ->and($result['page_data']['url'])->toBeNull()
        ->and($result['robots_txt']['found'])->toBeFalse()
        ->and($result['llms_txt']['found'])->toBeFalse()
        ->and($result['brand_mentions'])->toHaveKeys(['wikipedia', 'wikidata']);
});

it('detects wikipedia brand mention when found', function (): void {
    Http::fake([
        'en.wikipedia.org/*' => Http::response([
            'query' => ['search' => [
                ['title' => 'Acme Corporation', 'snippet' => 'Acme is a famous company.'],
            ]],
        ], 200),
        'www.wikidata.org/*' => Http::response(['search' => []], 200),
        '*' => Http::response(null, 404),
    ]);

    $analyzer = new GeoAnalyzer;
    $result = $analyzer->analyzeWithoutWebsite('Acme Corporation');

    expect($result['brand_mentions']['wikipedia']['found'])->toBeTrue()
        ->and($result['brand_mentions']['wikipedia']['pages'][0]['title'])->toBe('Acme Corporation');
});

it('extracts JSON-LD schema markup from page', function (): void {
    $html = <<<'HTML'
<html><head>
<script type="application/ld+json">{"@context":"https://schema.org","@type":"Organization","name":"Test Corp"}</script>
</head><body>Test Corp offers services.</body></html>
HTML;

    Http::fake([
        'example.com' => Http::response($html, 200, ['Content-Type' => 'text/html']),
        'example.com/robots.txt' => Http::response(null, 404),
        'example.com/llms.txt' => Http::response(null, 404),
        'en.wikipedia.org/*' => Http::response(['pages' => []], 200),
        'www.wikidata.org/*' => Http::response(['search' => []], 200),
    ]);

    $analyzer = new GeoAnalyzer;
    $result = $analyzer->analyze('https://example.com');

    expect($result['schema_markup'])->not->toBeEmpty()
        ->and($result['schema_markup'][0]['type'])->toBe('Organization');
});
