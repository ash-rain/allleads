<?php

namespace Database\Factories;

use App\Models\ProspectingResult;
use App\Models\ProspectingSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProspectingResult>
 */
class ProspectingResultFactory extends Factory
{
    protected $model = ProspectingResult::class;

    public function definition(): array
    {
        return [
            'prospecting_session_id' => ProspectingSession::factory(),
            'source' => fake()->randomElement(['foursquare', 'osm']),
            'source_id' => fake()->unique()->uuid(),
            'title' => fake()->company(),
            'category' => fake()->randomElement(['dentist', 'restaurant', 'plumber', 'hairdresser']),
            'address' => fake()->address(),
            'latitude' => fake()->latitude(51.0, 54.0),
            'longitude' => fake()->longitude(-3.0, 0.0),
            'phone' => fake()->unique()->phoneNumber(),
            'website' => fake()->optional(0.6)->url(),
            'email' => fake()->optional(0.3)->companyEmail(),
            'review_rating' => fake()->optional(0.7)->randomFloat(1, 1.0, 5.0),
            'review_count' => fake()->optional(0.7)->numberBetween(1, 500),
            'signals' => [],
            'raw_data' => null,
            'status' => ProspectingResult::STATUS_NEW,
            'lead_id' => null,
        ];
    }

    public function forSession(ProspectingSession $session): static
    {
        return $this->state(['prospecting_session_id' => $session->id]);
    }

    public function selected(): static
    {
        return $this->state(['status' => ProspectingResult::STATUS_SELECTED]);
    }

    public function dismissed(): static
    {
        return $this->state(['status' => ProspectingResult::STATUS_DISMISSED]);
    }

    public function imported(): static
    {
        return $this->state(['status' => ProspectingResult::STATUS_IMPORTED]);
    }

    public function duplicate(): static
    {
        return $this->state(['status' => ProspectingResult::STATUS_DUPLICATE]);
    }

    public function noWebsite(): static
    {
        return $this->state([
            'website' => null,
            'signals' => ['no_website'],
        ]);
    }

    public function lowRating(): static
    {
        return $this->state([
            'review_rating' => 2.5,
            'signals' => ['low_rating'],
        ]);
    }
}
