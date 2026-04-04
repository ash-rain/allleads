<?php

namespace App\Jobs;

use App\Models\AiSetting;
use App\Models\BusinessSetting;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\LeadTrendAnalysis;
use App\Models\User;
use App\Notifications\TrendAnalysisFailedNotification;
use App\Services\Ai\AiProviderFactory;
use App\Services\Intelligence\TrendResearcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RunTrendAnalysisJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 120;

    public function __construct(
        public readonly Lead $lead,
        public readonly int $userId,
    ) {}

    public function handle(TrendResearcher $researcher): void
    {
        $analysis = LeadTrendAnalysis::updateOrCreate(
            ['lead_id' => $this->lead->id],
            [
                'status' => LeadTrendAnalysis::STATUS_PENDING,
                'raw_data' => null,
                'result' => null,
                'error_message' => null,
                'started_at' => now(),
                'completed_at' => null,
            ]
        );

        $topic = $analysis->topic ?? trim(implode(' ', array_filter([
            $this->lead->title,
            $this->lead->category,
        ])));

        $rawData = $researcher->research($topic);
        $analysis->update(['raw_data' => $rawData]);

        $setting = AiSetting::singleton();
        $provider = AiProviderFactory::makeWithFallback($setting);

        $system = $this->buildSystemPrompt($setting->language ?? 'English');
        $user = $this->buildUserPrompt($topic, $rawData);

        $raw = $provider->complete($system, $user, [
            'model' => $setting->model,
            'temperature' => (float) $setting->temperature,
            'max_tokens' => (int) $setting->max_tokens,
            'timeout' => (int) $setting->timeout,
        ]);

        $result = $this->parseJsonResponse($raw);
        $result = $this->adjustScoreForDiscovery($result, $rawData);

        $analysis->update([
            'topic' => $topic,
            'status' => LeadTrendAnalysis::STATUS_COMPLETED,
            'result' => $result,
            'provider' => $setting->provider,
            'model' => $setting->model,
            'completed_at' => now(),
        ]);

        LeadActivity::record($this->lead, 'trend_analysis_completed', [
            'topic' => $topic,
            'provider' => $setting->provider,
            'model' => $setting->model,
        ], $this->userId);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('RunTrendAnalysisJob failed', [
            'lead_id' => $this->lead->id,
            'error' => $e->getMessage(),
        ]);

        LeadTrendAnalysis::where('lead_id', $this->lead->id)
            ->whereIn('status', [LeadTrendAnalysis::STATUS_PENDING])
            ->update([
                'status' => LeadTrendAnalysis::STATUS_FAILED,
                'error_message' => $e->getMessage(),
            ]);

        User::find($this->userId)?->notify(
            new TrendAnalysisFailedNotification($this->lead, $e->getMessage())
        );

        LeadActivity::record($this->lead, 'trend_analysis_failed', [
            'error' => $e->getMessage(),
        ], $this->userId);
    }

    // ─── Helpers ────────────────────────────────────────────────────────────

    private function buildSystemPrompt(string $language): string
    {
        $businessSetting = BusinessSetting::singleton();
        $businessContext = $businessSetting->toPromptContext();
        $services = $businessSetting->key_services ?? 'our services';

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

    /**
     * @param  array<string, mixed>  $rawData
     */
    private function buildUserPrompt(string $topic, array $rawData): string
    {
        $lines = ["Analyse market trends for the topic: {$topic}", ''];

        $reddit = $rawData['reddit'] ?? [];
        if (! empty($reddit)) {
            $lines[] = '=== Reddit Discussions (last 30 days) ===';
            foreach (array_slice($reddit, 0, 10) as $post) {
                $lines[] = "- [{$post['subreddit']}] {$post['title']} ({$post['score']} points, {$post['comments']} comments)";
            }
            $lines[] = '';
        }

        $hn = $rawData['hackernews'] ?? [];
        if (! empty($hn)) {
            $lines[] = '=== Hacker News Stories ===';
            foreach (array_slice($hn, 0, 10) as $story) {
                $lines[] = "- {$story['title']} ({$story['points']} points, {$story['comments']} comments)";
            }
            $lines[] = '';
        }

        $polymarket = $rawData['polymarket'] ?? [];
        if (! empty($polymarket)) {
            $lines[] = '=== Prediction Markets ===';
            foreach ($polymarket as $event) {
                $lines[] = "- {$event['title']}";
            }
            $lines[] = '';
        }

        $news = $rawData['news'] ?? [];
        if (! empty($news)) {
            $lines[] = '=== Google News Articles ===';
            foreach (array_slice($news, 0, 10) as $article) {
                $lines[] = "- {$article['title']}".(! empty($article['source']) ? " ({$article['source']})" : '');
            }
            $lines[] = '';
        }

        if (empty($reddit) && empty($hn) && empty($polymarket) && empty($news)) {
            $lines[] = '(No social data found for this topic)';
        }

        return implode("\n", $lines);
    }

    /**
     * @param  array<string, mixed>  $result
     * @param  array<string, mixed>  $rawData
     * @return array<string, mixed>
     */
    private function adjustScoreForDiscovery(array $result, array $rawData): array
    {
        $total = count($rawData['reddit'] ?? [])
            + count($rawData['hackernews'] ?? [])
            + count($rawData['news'] ?? []);

        $score = (int) ($result['relevance_score'] ?? 50);

        if ($total === 0) {
            $score = min($score, 30);
        } elseif ($total < 5) {
            $score = (int) round($score * 0.6);
        } elseif ($total < 15) {
            $score = (int) round($score * 0.8);
        }

        $result['relevance_score'] = max(1, min(100, $score));

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    private function parseJsonResponse(string $raw): array
    {
        // Strip <think>...</think> blocks from reasoning models (e.g. DeepSeek)
        $cleaned = preg_replace('/<think>[\s\S]*?<\/think>/i', '', $raw);
        $cleaned = preg_replace('/^```(?:json)?\s*/m', '', $cleaned ?? $raw);
        $cleaned = preg_replace('/\s*```$/m', '', $cleaned ?? $raw);

        $decoded = json_decode(trim($cleaned ?? ''), true);

        if (! is_array($decoded)) {
            throw new \RuntimeException('AI returned invalid JSON: '.mb_substr($raw, 0, 200));
        }

        return $decoded;
    }
}
