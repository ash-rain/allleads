<?php

namespace App\Ai\Tools\Website;

use App\Services\Intelligence\WebsiteScraper;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class ScrapeWebsite implements Tool
{
    public function description(): string
    {
        return 'Scrape a website URL and extract structured data including company name, technology stack, social media links, team members, pricing tiers, job postings, contact information, company size signals, and a text excerpt of the homepage.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'url' => $schema
                ->string()
                ->description('The full URL of the website to scrape (e.g. https://example.com).')
                ->required(),
        ];
    }

    public function handle(Request $request): string
    {
        $url = $request->get('url');

        /** @var WebsiteScraper $scraper */
        $scraper = app(WebsiteScraper::class);

        $result = $scraper->scrape($url);

        return json_encode($result);
    }
}
