<?php

namespace App\Ai\Agents;

use App\Ai\Tools\Website\ScrapeWebsite;
use App\Models\Business;
use App\Models\Lead;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Promptable;

class WebsiteAnalysisAgent implements Agent, HasStructuredOutput, HasTools
{
    use Promptable;

    public function __construct(
        private readonly Lead $lead,
        private readonly ?Business $business = null,
    ) {}

    public function instructions(): string
    {
        $language = $this->business?->aiSetting?->language ?? 'English';
        $businessContext = $this->business?->toPromptContext() ?? 'You are targeting B2B prospects for outreach.';
        $services = $this->business?->key_services ?? 'web development services';

        return <<<PROMPT
You are an expert B2B sales intelligence analyst. You represent a specific business and must tailor your analysis to it.

{$businessContext}

Using the above as context for who WE are, analyse the PROSPECT's website data and return a structured JSON object with exactly these keys:

- business_overview (string): 2-3 sentence company summary of the prospect
- value_proposition (string): what the prospect sells and to whom
- target_market (string): customer segments the prospect serves
- revenue_model (string): how the prospect makes money
- competitive_position (string): prospect's market position vs competitors
- growth_signals (string): prospect's expansion indicators (hiring, new products, etc.)
- tech_maturity (string): prospect's digital sophistication assessment
- sales_angles (array of 3 strings): specific outreach angles WE can use based on OUR services and what WE can offer this prospect
- pain_points (array of strings): likely challenges this prospect has that OUR specific services can solve
- overall_score (integer 1-100): fit score for {$services}

IMPORTANT: Write ALL analysis text values in {$language}.
The sales_angles and pain_points must be grounded in our specific services and value proposition above — not generic.
Return ONLY valid JSON with those 10 keys, no extra text or markdown.
PROMPT;
    }

    public function tools(): iterable
    {
        return [
            new ScrapeWebsite,
        ];
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'business_overview' => $schema->string()->required(),
            'value_proposition' => $schema->string()->required(),
            'target_market' => $schema->string()->required(),
            'revenue_model' => $schema->string()->required(),
            'competitive_position' => $schema->string()->required(),
            'growth_signals' => $schema->string()->required(),
            'tech_maturity' => $schema->string()->required(),
            'sales_angles' => $schema->array()->required(),
            'pain_points' => $schema->array()->required(),
            'overall_score' => $schema->integer()->min(1)->max(100)->required(),
        ];
    }
}
