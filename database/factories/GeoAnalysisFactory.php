<?php

namespace Database\Factories;

use App\Models\GeoAnalysis;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GeoAnalysis>
 */
class GeoAnalysisFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'url' => $this->faker->url(),
            'status' => GeoAnalysis::STATUS_COMPLETED,
            'raw_data' => [
                'page_data' => [
                    'title' => $this->faker->company().' - '.$this->faker->catchPhrase(),
                    'word_count' => $this->faker->numberBetween(300, 2000),
                ],
                'robots_txt' => ['exists' => true, 'ai_crawler_status' => []],
                'llms_txt' => ['llms_txt' => ['exists' => false], 'llms_full_txt' => ['exists' => false]],
                'citability' => ['average_score' => $this->faker->randomFloat(1, 35, 75), 'grade' => 'C'],
                'brand_mentions' => ['wikipedia' => ['has_wikipedia_page' => false]],
                'schema_markup' => ['detected_types' => [], 'count' => 0],
                'technical_seo' => ['has_ssl' => true, 'has_ssr_content' => true],
            ],
            'result' => [
                'geo_score' => $this->faker->numberBetween(40, 85),
                'ai_visibility_summary' => $this->faker->sentences(2, true),
                'citability_assessment' => $this->faker->sentence(),
                'crawler_access_summary' => $this->faker->sentence(),
                'brand_authority_assessment' => $this->faker->sentence(),
                'schema_assessment' => $this->faker->sentence(),
                'technical_assessment' => $this->faker->sentence(),
                'sales_angles' => [$this->faker->sentence(), $this->faker->sentence(), $this->faker->sentence()],
                'quick_wins' => [$this->faker->sentence(), $this->faker->sentence()],
                'platform_recommendations' => [$this->faker->sentence()],
            ],
            'provider' => 'openrouter',
            'model' => 'meta-llama/llama-3.1-8b-instruct:free',
            'error_message' => null,
            'started_at' => now()->subSeconds(25),
            'completed_at' => now(),
            'archived_at' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state([
            'status' => GeoAnalysis::STATUS_PENDING,
            'raw_data' => null,
            'result' => null,
            'completed_at' => null,
        ]);
    }

    public function failed(): static
    {
        return $this->state([
            'status' => GeoAnalysis::STATUS_FAILED,
            'raw_data' => null,
            'result' => null,
            'error_message' => 'Failed to analyse website: connection timeout.',
            'completed_at' => null,
        ]);
    }
}
