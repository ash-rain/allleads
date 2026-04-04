<?php

namespace App\Jobs;

use App\Models\AiSetting;
use App\Models\BusinessSetting;
use App\Models\TrendAnalysis;
use App\Services\Ai\AiProviderFactory;
use App\Services\Intelligence\TrendResearcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RunCompanyTrendAnalysisJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 120;

    public function __construct(
        public readonly string $topic,
        public readonly int $userId,
    ) {}

    public function handle(TrendResearcher $researcher): void
    {
        $analysis = TrendAnalysis::create([
            'user_id' => $this->userId,
            'topic' => $this->topic,
            'status' => TrendAnalysis::STATUS_PENDING,
            'started_at' => now(),
        ]);

        $rawData = $researcher->research($this->topic);
        $analysis->update(['raw_data' => $rawData]);

        $setting = AiSetting::singleton();
        $provider = AiProviderFactory::makeWithFallback($setting);

        $system = $this->buildSystemPrompt($setting->language ?? 'English');
        $user = $this->buildUserPrompt($this->topic, $rawData);

        $raw = $provider->complete($system, $user, [
            'model' => $setting->model,
            'temperature' => (float) $setting->temperature,
            'max_tokens' => (int) $setting->max_tokens,
            'timeout' => (int) $setting->timeout,
        ]);

        $result = $this->parseJsonResponse($raw);
        $result = $this->adjustScoreForDiscovery($result, $rawData);

        $analysis->update([
            'status' => TrendAnalysis::STATUS_COMPLETED,
            'result' => $result,
            'provider' => $setting->provider,
            'model' => $setting->model,
            'completed_at' => now(),
        ]);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('RunCompanyTrendAnalysisJob failed', [
            'topic' => $this->topic,
            'error' => $e->getMessage(),
        ]);

        TrendAnalysis::where('user_id', $this->userId)
            ->where('topic', $this->topic)
            ->whereIn('status', [TrendAnalysis::STATUS_PENDING])
            ->update([
                'status' => TrendAnalysis::STATUS_FAILED,
                'error_message' => $e->getMessage(),
            ]);
    }

    // ─── Helpers ────────────────────────────────────────────────────────────

    private function buildSystemPrompt(string $language): string
    {
        $businessSetting = BusinessSetting::singleton();
        $businessContext = $businessSetting->toPromptContext();
        $services = $businessSetting->key_services ?? 'our services';

        return <<<PROMPT
You are a senior market intelligence analyst. You represent a specific business and are helping their team understand market trends.\n\n{$businessContext}\n\nUsing the above as context for who WE are, analyse the provided social discussion and prediction market data and return a structured JSON object with exactly these keys:

- market_overview (string): 2-3 sentence summary of current market discussion and trends
- trending_topics (array of strings): top 3-5 trending sub-topics or themes
- community_sentiment (string): overall community sentiment with brief explanation
- opportunities (array of 2-3 strings): strategic opportunities for OUR business based on these trends and OUR services ({$services})
- talking_points (array of 3 strings): key points OUR team should be aware of and can use in sales conversations
- prediction_markets (string): brief summary of prediction market signals if available, otherwise null
- relevance_score (integer 1-100): strategic relevance of these trends for OUR business

IMPORTANT: Write ALL analysis text values in {$language}.
Return ONLY valid JSON with those 7 keys, no extra text or markdown.
PROMPT;
    }

    /**
     * @param  array<string, mixed>  $rawData
     */
    private function buildUserPrompt(string $topic, array $rawData): string
    {
        $lines = ["Analyse market trends for: {$topic}", ''];

        $reddit = $rawData['reddit'] ?? [];
        if (! empty($reddit)) {
            $lines[] = '=== Reddit Discussions ===';
            foreach (array_slice($reddit, 0, 10) as $post) {
                $lines[] = "- [{$post['subreddit']}] {$post['title']} ({$post['score']} points)";
            }
            $lines[] = '';
        }

        $hn = $rawData['hackernews'] ?? [];
        if (! empty($hn)) {
            $lines[] = '=== Hacker News Stories ===';
            foreach (array_slice($hn, 0, 10) as $story) {
                $lines[] = "- {$story['title']} ({$story['points']} points)";
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
