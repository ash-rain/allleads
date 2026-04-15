<?php

namespace App\Ai\Agents;

use App\Ai\Tools\Email\FetchLeadAnalyses;
use App\Ai\Tools\Email\FetchPreviousDrafts;
use App\Models\AiSetting;
use App\Models\Lead;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Promptable;

class ColdEmailAgent implements Agent, HasStructuredOutput, HasTools
{
    use Promptable;

    public function __construct(
        private readonly Lead $lead,
        private readonly ?AiSetting $setting = null,
    ) {}

    public function instructions(): string
    {
        // If a custom system prompt is set, it fully overrides the built-in one.
        // Supported placeholders: {lead_name}, {category}, {rating}, {address}
        if ($this->setting?->custom_system_prompt) {
            return str_replace(
                ['{lead_name}', '{category}', '{rating}', '{address}'],
                [
                    $this->lead->title,
                    $this->lead->category ?? '',
                    $this->lead->review_rating ?? '',
                    $this->lead->address ?? '',
                ],
                $this->setting->custom_system_prompt
            );
        }

        $business = $this->lead->business;
        $businessContext = $business ? $business->toPromptContext() : 'You are targeting B2B prospects for outreach.';

        $tone = $this->setting?->tone ?? 'professional';
        $language = $this->setting?->language ?? 'English';
        $length = $this->setting?->length ?? 'medium';
        $personalisation = $this->setting?->personalisation ?? 'medium';
        $openerStyle = $this->setting?->opener_style ?? 'question';

        $lengthGuide = match ($length) {
            'short' => '3–4 sentences',
            'long' => '6–8 sentences',
            default => '4–5 sentences',
        };

        $personGuide = match ($personalisation) {
            'high' => 'Weave in at least 2 specific details from the business profile (name, category, rating, location, website) to show deep research.',
            'low' => 'Keep the message brief and generic — no business-specific details.',
            default => 'Reference the business name and category naturally.',
        };

        $openerGuide = match ($openerStyle) {
            'question' => 'Open with a relevant question about their online presence.',
            'compliment' => 'Start with a genuine, specific compliment about their business.',
            'observation' => 'Open with an observation about a gap or opportunity you noticed.',
            'direct' => 'Get straight to the offer without a soft opener.',
        };

        $includes = [];
        if ($this->setting?->include_cta) {
            $includes[] = 'End with a clear call-to-action (book a call or reply).';
        }
        if ($this->setting?->include_ps) {
            $includes[] = 'Add a P.S. line with a bonus offer or urgency.';
        }

        $includeText = $includes ? implode(' ', $includes) : '';

        return <<<PROMPT
{$businessContext}

You are an expert cold email copywriter representing the business above. Write cold outreach emails targeting local businesses.
Language: {$language}. Tone: {$tone}. Length: {$lengthGuide}. Personalisation: {$personGuide}.
Opener style: {$openerGuide}.
{$includeText}
Start your response with "Subject: [your subject]" on the first line, then a blank line, then the email body. Do NOT add placeholder text like [Name] — use the actual data provided.
PROMPT;
    }

    public function tools(): iterable
    {
        return [
            new FetchLeadAnalyses,
            new FetchPreviousDrafts,
        ];
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'subject' => $schema->string()->required(),
            'body' => $schema->string()->required(),
        ];
    }
}
