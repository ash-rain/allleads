<?php

namespace App\Ai\Tools\Trend;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class SearchGoogleNews implements Tool
{
    private const USER_AGENT = 'allleads-bot/1.0 (research tool)';

    public function description(): string
    {
        return 'Search Google News RSS feed for recent news articles related to a topic. Returns articles including title, URL, source, and publication date.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'topic' => $schema
                ->string()
                ->description('The topic or keyword to search for on Google News.')
                ->required(),
        ];
    }

    public function handle(Request $request): string
    {
        $topic = $request->get('topic');

        try {
            $response = Http::timeout(10)
                ->withHeaders(['User-Agent' => self::USER_AGENT])
                ->get('https://news.google.com/rss/search', [
                    'q' => $topic,
                    'hl' => 'en-US',
                    'gl' => 'US',
                    'ceid' => 'US:en',
                ]);

            $articles = $this->parseGoogleNews($response);
        } catch (\Throwable $e) {
            Log::warning('SearchGoogleNews tool: request failed', ['error' => $e->getMessage()]);
            $articles = [];
        }

        return json_encode($articles);
    }

    private function parseGoogleNews(mixed $response): array
    {
        try {
            if (! $response || $response->failed()) {
                return [];
            }

            $xml = simplexml_load_string($response->body());

            if (! $xml || ! isset($xml->channel->item)) {
                return [];
            }

            $results = [];

            foreach ($xml->channel->item as $item) {
                $title = (string) $item->title;
                $source = (string) ($item->source ?? '');

                if (empty($title)) {
                    continue;
                }

                // Google News appends " - Source Name" to titles — strip it to avoid duplication
                if ($source && str_ends_with($title, ' - '.$source)) {
                    $title = substr($title, 0, -strlen(' - '.$source));
                }

                $results[] = [
                    'title' => $title,
                    'url' => (string) $item->link,
                    'source' => $source,
                    'published_at' => ! empty($item->pubDate)
                        ? date('Y-m-d', strtotime((string) $item->pubDate))
                        : null,
                ];

                if (count($results) >= 20) {
                    break;
                }
            }

            return $results;
        } catch (\Throwable $e) {
            Log::warning('SearchGoogleNews tool: parse failed', ['error' => $e->getMessage()]);

            return [];
        }
    }
}
