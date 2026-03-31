<?php

namespace Database\Factories;

use App\Models\Lead;
use App\Models\LeadWebsiteAnalysis;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeadWebsiteAnalysis>
 */
class LeadWebsiteAnalysisFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'lead_id' => Lead::factory(),
            'status' => LeadWebsiteAnalysis::STATUS_COMPLETED,
            'scraped_data' => [
                'company_name' => $this->faker->company(),
                'tech_stack' => ['WordPress', 'PHP'],
                'social_links' => [
                    'linkedin' => 'https://linkedin.com/company/'.$this->faker->slug(),
                ],
                'team_members' => [],
                'pricing_tiers' => [],
                'job_postings' => [],
                'contact_info' => ['email' => $this->faker->safeEmail()],
                'company_size_signals' => null,
            ],
            'result' => [
                'business_overview' => $this->faker->sentences(2, true),
                'value_proposition' => $this->faker->sentence(),
                'target_market' => $this->faker->sentence(),
                'revenue_model' => $this->faker->sentence(),
                'competitive_position' => $this->faker->sentence(),
                'growth_signals' => $this->faker->sentence(),
                'tech_maturity' => $this->faker->sentence(),
                'sales_angles' => [
                    $this->faker->sentence(),
                    $this->faker->sentence(),
                    $this->faker->sentence(),
                ],
                'pain_points' => [
                    $this->faker->sentence(),
                    $this->faker->sentence(),
                ],
                'overall_score' => $this->faker->numberBetween(40, 95),
            ],
            'provider' => 'openrouter',
            'model' => 'meta-llama/llama-3.1-8b-instruct:free',
            'error_message' => null,
            'started_at' => now()->subSeconds(10),
            'completed_at' => now(),
        ];
    }

    public function pending(): static
    {
        return $this->state([
            'status' => LeadWebsiteAnalysis::STATUS_PENDING,
            'scraped_data' => null,
            'result' => null,
            'completed_at' => null,
        ]);
    }

    public function failed(): static
    {
        return $this->state([
            'status' => LeadWebsiteAnalysis::STATUS_FAILED,
            'scraped_data' => null,
            'result' => null,
            'error_message' => 'Failed to scrape website: connection timeout.',
            'completed_at' => null,
        ]);
    }
}
