<?php

namespace Database\Factories;

use App\Models\Lead;
use App\Models\LeadNote;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeadNote>
 */
class LeadNoteFactory extends Factory
{
    protected $model = LeadNote::class;

    public function definition(): array
    {
        return [
            'lead_id' => Lead::factory(),
            'user_id' => User::factory(),
            'type' => 'note',
            'body' => fake()->sentence(10),
            'duration' => null,
            'outcome' => null,
        ];
    }

    public function call(): static
    {
        return $this->state([
            'type' => 'call',
            'duration' => fake()->numberBetween(2, 30),
            'outcome' => fake()->randomElement(['interested', 'not_interested', 'callback']),
        ]);
    }
}
