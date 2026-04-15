<?php

namespace App\Ai\Agents;

use App\Ai\Tools\Trend\SearchGoogleNews;
use App\Ai\Tools\Trend\SearchHackerNews;
use App\Ai\Tools\Trend\SearchPolymarket;
use App\Ai\Tools\Trend\SearchReddit;
use App\Models\Business;
use App\Models\Lead;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Promptable;

class TrendAnalysisAgent implements Agent, HasStructuredOutput, HasTools
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
You are a market trend analyst for a B2B sales team. You represent a specific business and must tailor your analysis to find commercial opportunities.

{$businessContext}

Using the above as context for who WE are, analyse the provided social discussion and prediction market data about a lead's industry topic and return a structured JSON object with exactly these keys:

- market_overview (string): 2-3 sentence summary of current market discussion and trends
- trending_topics (array of strings): top 3-5 trending sub-topics or themes from the data
- community_sentiment (string): overall community sentiment about this topic (positive/negative/mixed) with brief explanation
- opportunities (array of 2-3 strings): specific business opportunities WE can leverage with this lead based on trends and OUR services
- talking_points (array of 3 strings): specific conversation starters WE can use when reaching out to this lead, grounded in recent trends
- prediction_markets (string): brief summary of prediction market signals if available, otherwise null
- relevance_score (integer 1-100): how relevant these trends are for our sales pitch to this specific lead

IMPORTANT: Write ALL analysis text values in {$language}.
The opportunities and talking_points must be grounded in our specific services ({$services}) — not generic.
Return ONLY valid JSON with those 7 keys, no extra text or markdown.
PROMPT;
    }

    public function tools(): iterable
    {
        return [
            new SearchReddit,
            new SearchHackerNews,
            new SearchPolymarket,
            new SearchGoogleNews,
        ];
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'market_overview' => $schema->string()->required(),
            'trending_topics' => $schema->array()->required(),
            'community_sentiment' => $schema->string()->required(),
            'opportunities' => $schema->array()->required(),
            'talking_points' => $schema->array()->required(),
            'prediction_markets' => $schema->string(),
            'relevance_score' => $schema->integer()->min(1)->max(100)->required(),
        ];
    }

    protected function topic(): string
    {
        if ($this->subject instanceof Lead) {
            return trim(implode(' ', array_filter([
                $this->subject->title,
                $this->subject->category,
            ])));
        }

        return $this->subject;
    }
}
