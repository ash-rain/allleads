<?php

namespace App\Jobs;

use App\Models\AiSetting;
use App\Models\EmailCampaign;
use App\Models\EmailDraft;
use App\Models\EmailThread;
use App\Models\Lead;
use App\Models\LeadProspectAnalysis;
use App\Models\LeadWebsiteAnalysis;
use App\Models\User;
use App\Notifications\DraftFailedNotification;
use App\Services\Ai\AiProviderFactory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateColdEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 300;

    public function __construct(
        public readonly Lead $lead,
        public readonly EmailThread $thread,
        public readonly ?EmailCampaign $campaign,
        public readonly int $userId,
    ) {}

    public function handle(): void
    {
        $setting = AiSetting::singleton();
        $provider = AiProviderFactory::makeWithFallback($setting);

        $system = $this->buildSystemPrompt($setting, $this->lead);
        $user = $this->buildUserPrompt($this->lead, $setting);

        $body = $provider->complete($system, $user, [
            'model' => $setting->model,
            'temperature' => (float) $setting->temperature,
            'max_tokens' => (int) $setting->max_tokens,
            'timeout' => (int) $setting->timeout,
        ]);

        $subject = $this->generateSubject($this->lead);

        EmailDraft::create([
            'lead_id' => $this->lead->id,
            'campaign_id' => $this->campaign?->id,
            'thread_id' => $this->thread->id,
            'subject' => $subject,
            'body' => $body,
            'status' => 'draft',
            'version' => 1,
        ]);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('GenerateColdEmailJob failed', [
            'lead_id' => $this->lead->id,
            'error' => $e->getMessage(),
        ]);

        User::find($this->userId)?->notify(
            new DraftFailedNotification($this->lead, $e->getMessage())
        );
    }

    // ─── Prompt Builders ────────────────────────────────────────────────────

    private function buildSystemPrompt(AiSetting $setting, Lead $lead): string
    {
        // If a custom system prompt is set, it fully overrides the built-in one.
        // Supported placeholders: {lead_name}, {category}, {rating}, {address}
        if ($setting->custom_system_prompt) {
            return str_replace(
                ['{lead_name}', '{category}', '{rating}', '{address}'],
                [
                    $lead->title,
                    $lead->category ?? '',
                    $lead->review_rating ?? '',
                    $lead->address ?? '',
                ],
                $setting->custom_system_prompt
            );
        }

        $tone = $setting->tone ?? 'professional';
        $language = $setting->language ?? 'English';
        $length = $setting->length ?? 'medium';
        $personalisation = $setting->personalisation ?? 'medium';
        $openerStyle = $setting->opener_style ?? 'question';

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
        if ($setting->include_portfolio) {
            $includes[] = 'Mention our portfolio/past results briefly.';
        }
        if ($setting->include_audit) {
            $includes[] = 'Offer a free website audit.';
        }
        if ($setting->include_cta) {
            $includes[] = 'End with a clear call-to-action (book a call or reply).';
        }
        if ($setting->include_ps) {
            $includes[] = 'Add a P.S. line with a bonus offer or urgency.';
        }

        $includeText = $includes ? implode(' ', $includes) : '';

        return <<<PROMPT
You are an expert cold email copywriter for a web development agency. Write cold outreach emails targeting local businesses.
Language: {$language}. Tone: {$tone}. Length: {$lengthGuide}. Personalisation: {$personGuide}.
Opener style: {$openerGuide}.
{$includeText}
Do NOT include a subject line — only the email body. Do NOT add placeholder text like [Name] — use the actual data provided.
PROMPT;
    }

    private function buildUserPrompt(Lead $lead, AiSetting $setting): string
    {
        $parts = ["Business name: {$lead->title}"];

        if ($lead->category) {
            $parts[] = "Category: {$lead->category}";
        }
        if ($lead->review_rating) {
            $parts[] = "Google review rating: {$lead->review_rating}/5";
        }
        if ($lead->address) {
            $parts[] = "Location: {$lead->address}";
        }
        if ($lead->website) {
            $parts[] = "Website: {$lead->website}";
        } else {
            $parts[] = 'No website found.';
        }

        if ($lead->prospectAnalysis?->status === LeadProspectAnalysis::STATUS_COMPLETED) {
            $prospectResult = $lead->prospectAnalysis->result ?? [];
            $parts[] = "\nProspect Analysis:";
            if (! empty($prospectResult['opportunity'])) {
                $parts[] = "- Opportunity: {$prospectResult['opportunity']}";
            }
            if (! empty($prospectResult['outreach_strategy'])) {
                $parts[] = "- Outreach Strategy: {$prospectResult['outreach_strategy']}";
            }
        }

        if ($lead->websiteAnalysis?->status === LeadWebsiteAnalysis::STATUS_COMPLETED) {
            $websiteResult = $lead->websiteAnalysis->result ?? [];
            $parts[] = "\nWebsite Analysis:";
            if (! empty($websiteResult['business_overview'])) {
                $parts[] = "- Business Overview: {$websiteResult['business_overview']}";
            }
            if (! empty($websiteResult['sales_angles'])) {
                $parts[] = '- Sales Angles: '.implode(', ', (array) $websiteResult['sales_angles']);
            }
            if (! empty($websiteResult['pain_points'])) {
                $parts[] = '- Pain Points: '.implode(', ', (array) $websiteResult['pain_points']);
            }
        }

        return 'Write a cold email for this lead:'."\n".implode("\n", $parts);
    }

    private function generateSubject(Lead $lead): string
    {
        return "Quick question about {$lead->title}'s online presence";
    }
}
