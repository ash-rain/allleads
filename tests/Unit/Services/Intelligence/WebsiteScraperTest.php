<?php

use App\Services\Intelligence\WebsiteScraper;
use Illuminate\Support\Facades\Http;

$scraper = fn () => new WebsiteScraper;

$invoke = function (string $method, mixed ...$args) use ($scraper): mixed {
    $ref = new ReflectionMethod(WebsiteScraper::class, $method);
    $ref->setAccessible(true);

    return $ref->invoke($scraper(), ...$args);
};

it('extracts company name from og:site_name', function () use ($invoke): void {
    $html = '<meta property="og:site_name" content="Acme Corp" />';

    expect($invoke('extractCompanyName', $html))->toBe('Acme Corp');
});

it('extracts company name from title tag when og:site_name missing', function () use ($invoke): void {
    $html = '<title>Acme Corp | Best Products</title>';

    expect($invoke('extractCompanyName', $html))->toStartWith('Acme Corp');
});

it('extracts company name from h1 as fallback', function () use ($invoke): void {
    $html = '<h1>Acme Corp</h1>';

    expect($invoke('extractCompanyName', $html))->toBe('Acme Corp');
});

it('returns null for company name when html is empty', function () use ($invoke): void {
    expect($invoke('extractCompanyName', null))->toBeNull();
});

it('detects WordPress from wp-content signature', function () use ($invoke): void {
    $html = '<link rel="stylesheet" href="/wp-content/themes/mytheme/style.css">';

    expect($invoke('detectTechStack', $html))->toContain('WordPress');
});

it('detects Shopify from cdn.shopify.com', function () use ($invoke): void {
    $html = '<script src="https://cdn.shopify.com/s/files/1/0000/storefront.js"></script>';

    expect($invoke('detectTechStack', $html))->toContain('Shopify');
});

it('detects no tech stack when html is plain', function () use ($invoke): void {
    $html = '<html><body><h1>Hello</h1></body></html>';

    expect($invoke('detectTechStack', $html))->toBe([]);
});

it('extracts LinkedIn social link from footer', function () use ($invoke): void {
    $html = '<footer><a href="https://www.linkedin.com/company/acme-corp">LinkedIn</a></footer>';

    $links = $invoke('extractSocialLinks', $html);

    expect($links)->toHaveKey('linkedin')
        ->and($links['linkedin'])->toContain('linkedin.com/company/acme-corp');
});

it('extracts email from contact info', function () use ($invoke): void {
    $html = '<p>Contact us: hello@acme.com</p>';

    $contacts = $invoke('extractContactInfo', $html);

    expect($contacts)->toHaveKey('email')
        ->and($contacts['email'])->toBe('hello@acme.com');
});

it('does not fetch non-http schemes', function (): void {
    Http::fake(['*' => Http::response('malicious', 200)]);

    $scraper = new WebsiteScraper;
    $result = $scraper->scrape('ftp://malicious.example.com');

    Http::assertNothingSent();
    expect($result['company_name'])->toBeNull();
});
