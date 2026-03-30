<?php

namespace Database\Factories;

use App\Models\Lead;
use App\Models\LeadProspectAnalysis;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeadProspectAnalysis>
 */
class LeadProspectAnalysisFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'lead_id' => Lead::factory(),
            'status' => LeadProspectAnalysis::STATUS_COMPLETED,
            'result' => [
                'prospect_score' => $this->faker->numberBetween(40, 95),
                'company_fit' => $this->faker->sentences(2, true),
                'contact_intel' => $this->faker->sentence(),
                'opportunity' => $this->faker->sentence(),
                'competitive_intel' => $this->faker->sentence(),
                'outreach_strategy' => $this->faker->sentence(),
            ],
            'provider' => 'openrouter',
            'model' => 'meta-llama/llama-3.1-8b-instruct:free',
            'error_message' => null,
            'started_at' => now()->subSeconds(5),
            'completed_at' => now(),
        ];
    }

    public function pending(): static
    {
        return $this->state([
            'status' => LeadProspectAnalysis::STATUS_PENDING,
            'result' => null,
            'completed_at' => null,
        ]);
    }

    public function failed(): static
    {
        return $this->state([
            'status' => LeadProspectAnalysis::STATUS_FAILED,
            'result' => null,
            'error_message' => 'AI returned invalid JSON.',
            'completed_at' => null,
        ]);
    }
}
