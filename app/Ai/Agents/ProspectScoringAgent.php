<?php

namespace App\Ai\Agents;

use App\Models\Business;
use App\Models\Lead;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;

class ProspectScoringAgent implements Agent, HasStructuredOutput
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

        return <<<PROMPT
{$businessContext}

You are an expert B2B sales intelligence analyst representing the business above. Analyse the prospect and return a structured JSON object with exactly these keys:

- prospect_score (integer 1-100): overall fit score for our business
- company_fit (string): 2-3 sentence assessment of why this company is a good prospect
- contact_intel (string): key insights about the contact/decision-maker based on available data
- opportunity (string): the main business opportunity — what pain point or gap can be solved
- competitive_intel (string): likely existing solutions or competitors they might be using
- outreach_strategy (string): recommended first-contact approach and suggested opening line

IMPORTANT: Write ALL analysis text values in {$language}.
Return ONLY valid JSON with those 6 keys, no extra text or markdown.
PROMPT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'prospect_score' => $schema->integer()->min(1)->max(100)->required(),
            'company_fit' => $schema->string()->required(),
            'contact_intel' => $schema->string()->required(),
            'opportunity' => $schema->string()->required(),
            'competitive_intel' => $schema->string()->required(),
            'outreach_strategy' => $schema->string()->required(),
        ];
    }
}
