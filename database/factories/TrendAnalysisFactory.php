<?php

namespace Database\Factories;

use App\Models\TrendAnalysis;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TrendAnalysis>
 */
class TrendAnalysisFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'topic' => $this->faker->words(3, true),
            'status' => TrendAnalysis::STATUS_COMPLETED,
            'raw_data' => [
                'reddit' => [
                    ['title' => $this->faker->sentence(), 'subreddit' => 'technology', 'score' => 150, 'comments' => 42, 'url' => $this->faker->url(), 'created_at' => now()->subDays(5)->toDateString()],
                ],
                'hackernews' => [
                    ['title' => $this->faker->sentence(), 'url' => $this->faker->url(), 'points' => 200, 'comments' => 65, 'created_at' => now()->subDays(3)->toDateString()],
                ],
                'polymarket' => [],
                'meta' => ['topic' => $this->faker->words(3, true), 'days' => 30, 'fetched_at' => now()->toIsoString()],
            ],
            'result' => [
                'market_overview' => $this->faker->sentences(2, true),
                'trending_topics' => [
                    $this->faker->words(4, true),
                    $this->faker->words(4, true),
                ],
                'community_sentiment' => $this->faker->sentence(),
                'opportunities' => [
                    $this->faker->sentence(),
                    $this->faker->sentence(),
                ],
                'talking_points' => [
                    $this->faker->sentence(),
                    $this->faker->sentence(),
                ],
                'prediction_markets' => $this->faker->sentence(),
                'relevance_score' => $this->faker->numberBetween(40, 95),
            ],
            'provider' => 'openrouter',
            'model' => 'meta-llama/llama-3.1-8b-instruct:free',
            'error_message' => null,
            'started_at' => now()->subSeconds(15),
            'completed_at' => now(),
        ];
    }

    public function pending(): static
    {
        return $this->state([
            'status' => TrendAnalysis::STATUS_PENDING,
            'raw_data' => null,
            'result' => null,
            'completed_at' => null,
        ]);
    }

    public function failed(): static
    {
        return $this->state([
            'status' => TrendAnalysis::STATUS_FAILED,
            'raw_data' => null,
            'result' => null,
            'error_message' => 'Failed to research topic: connection timeout.',
            'completed_at' => null,
        ]);
    }
}
