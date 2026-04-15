<?php

namespace App\Ai\Agents;

use App\Ai\Tools\Geo\AnalyzeGeoReadiness;
use App\Models\Business;
use App\Models\Lead;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Promptable;

class GeoAnalysisAgent implements Agent, HasStructuredOutput, HasTools
{
    use Promptable;

    public function __construct(
        private readonly Lead|string $subject,
        private readonly ?Business $business = null,
    ) {}

    public function instructions(): string
    {
        $language = $this->business?->aiSetting?->language ?? 'English';
        $businessContext = $this->business?->toPromptContext() ?? 'You are targeting B2B prospects for outreach.';
        $services = $this->business?->key_services ?? 'our services';

        return <<<PROMPT
You are a GEO (Generative Engine Optimization) specialist for a B2B sales team. You represent a specific business and must tailor your analysis to find commercial opportunities.

{$businessContext}

Using the above as context for who WE are, analyse the provided website crawl data and return a structured JSON object with exactly these keys:

- geo_score (integer 1-100): overall GEO readiness score. Weight: citability 35%, crawler access 25%, brand authority 20%, schema markup 10%, technical SEO 10%
- ai_visibility_summary (string): 2-3 sentence summary of how visible this business is to AI systems right now
- citability_assessment (string): analysis of content quality for AI citation — strengths and weaknesses
- crawler_access_summary (string): summary of which AI crawlers can access the site and implications
- brand_authority_assessment (string): assessment of online brand authority based on Wikipedia/Wikidata presence
- schema_assessment (string): evaluation of structured data implementation and recommendations
- technical_assessment (string): key technical SEO signals for AI discoverability
- sales_angles (array of 2-3 strings): specific pitch angles WE can use with this lead based on their GEO gaps and OUR services ({$services})
- quick_wins (array of 3-5 strings): specific, actionable GEO improvements this business could implement quickly
- platform_recommendations (array of 2-4 strings): specific AI platforms or directories where this business should establish presence

IMPORTANT: Write ALL analysis text values in {$language}.
Return ONLY valid JSON with those 10 keys, no extra text or markdown.
PROMPT;
    }

    public function tools(): iterable
    {
        return [
            new AnalyzeGeoReadiness,
        ];
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'geo_score' => $schema->integer()->min(1)->max(100)->required(),
            'ai_visibility_summary' => $schema->string()->required(),
            'citability_assessment' => $schema->string()->required(),
            'crawler_access_summary' => $schema->string()->required(),
            'brand_authority_assessment' => $schema->string()->required(),
            'schema_assessment' => $schema->string()->required(),
            'technical_assessment' => $schema->string()->required(),
            'sales_angles' => $schema->array()->required(),
            'quick_wins' => $schema->array()->required(),
            'platform_recommendations' => $schema->array()->required(),
        ];
    }
}
