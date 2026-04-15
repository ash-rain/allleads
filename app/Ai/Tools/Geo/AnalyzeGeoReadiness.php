<?php

namespace App\Ai\Tools\Geo;

use App\Services\Intelligence\GeoAnalyzer;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class AnalyzeGeoReadiness implements Tool
{
    public function description(): string
    {
        return 'Analyze a website URL for GEO (Generative Engine Optimization) readiness. Checks robots.txt AI crawler access, llms.txt presence, citability scoring, brand mentions on Wikipedia/Wikidata, schema markup, and technical SEO signals. Provide a URL, a brand name, or both.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'url' => $schema
                ->string()
                ->description('The full URL of the website to analyze (e.g. https://example.com). Leave empty if analyzing by brand name only.'),
            'brand_name' => $schema
                ->string()
                ->description('The brand or company name to check for online mentions and knowledge-graph presence. Optional when a URL is provided.'),
        ];
    }

    public function handle(Request $request): string
    {
        $url = $request->get('url', '');
        $brandName = $request->get('brand_name') ?: null;

        /** @var GeoAnalyzer $analyzer */
        $analyzer = app(GeoAnalyzer::class);

        if (! empty($url)) {
            $result = $analyzer->analyze($url, $brandName);
        } else {
            $result = $analyzer->analyzeWithoutWebsite((string) $brandName);
        }

        return json_encode($result);
    }
}
