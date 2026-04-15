<?php

namespace App\Ai\Agents;

use App\Models\AiSetting;
use App\Models\EmailDraft;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;

class DraftRefinementAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public function __construct(
        private readonly EmailDraft $draft,
        private readonly ?AiSetting $setting = null,
    ) {}

    public function instructions(): string
    {
        $language = $this->setting?->language ?? 'English';
        $tone = $this->setting?->tone ?? 'professional';
        $business = $this->draft->lead?->business;
        $businessContext = $business ? $business->toPromptContext() : 'You are targeting B2B prospects for outreach.';

        return <<<PROMPT
{$businessContext}

You are an expert cold email copywriter representing the business above. You are editing an existing cold email draft.
Language: {$language}. Tone: {$tone}.
Apply ONLY the requested changes. Keep everything else as-is. Return only the updated email body — no subject line, no commentary.
PROMPT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'body' => $schema->string()->required(),
        ];
    }
}
