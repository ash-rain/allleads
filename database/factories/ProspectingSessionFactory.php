<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\ProspectingSession;
use App\Models\Territory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ProspectingSession>
 */
class ProspectingSessionFactory extends Factory
{
    protected $model = ProspectingSession::class;

    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'territory_id' => Territory::factory(),
            'uuid' => (string) Str::uuid(),
            'search_query' => fake()->randomElement(['dentist', 'plumber', 'restaurant', 'accountant', 'hairdresser']),
            'filters' => null,
            'status' => ProspectingSession::STATUS_PENDING,
            'sources_used' => null,
            'result_count' => 0,
            'imported_count' => 0,
            'dismissed_count' => 0,
            'searched_at' => null,
            'created_by' => User::factory(),
        ];
    }

    public function completed(): static
    {
        return $this->state([
            'status' => ProspectingSession::STATUS_COMPLETED,
            'sources_used' => ['foursquare', 'osm'],
            'searched_at' => now(),
        ]);
    }

    public function searching(): static
    {
        return $this->state([
            'status' => ProspectingSession::STATUS_SEARCHING,
        ]);
    }

    public function failed(): static
    {
        return $this->state([
            'status' => ProspectingSession::STATUS_FAILED,
        ]);
    }

    public function forBusiness(Business $business): static
    {
        return $this->state(['business_id' => $business->id]);
    }

    public function forTerritory(Territory $territory): static
    {
        return $this->state([
            'territory_id' => $territory->id,
            'business_id' => $territory->business_id,
        ]);
    }

    public function withFilters(array $filters): static
    {
        return $this->state(['filters' => $filters]);
    }
}
