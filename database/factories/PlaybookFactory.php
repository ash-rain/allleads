<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\Playbook;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Playbook>
 */
class PlaybookFactory extends Factory
{
    protected $model = Playbook::class;

    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'name' => fake()->words(3, true),
            'description' => fake()->optional()->sentence(),
            'icon' => null,
            'filters' => [],
            'sort_order' => fake()->numberBetween(0, 10),
            'is_active' => true,
        ];
    }

    public function forBusiness(Business $business): static
    {
        return $this->state(['business_id' => $business->id]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function withFilters(array $filters): static
    {
        return $this->state(['filters' => $filters]);
    }
}
