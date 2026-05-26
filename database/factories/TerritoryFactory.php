<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\Territory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Territory>
 */
class TerritoryFactory extends Factory
{
    protected $model = Territory::class;

    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'name' => fake()->city().' Area',
            'latitude' => fake()->latitude(51.0, 54.0),   // UK-ish range
            'longitude' => fake()->longitude(-3.0, 0.0),
            'radius_km' => fake()->randomElement([5.0, 10.0, 15.0, 25.0]),
            'created_by' => User::factory(),
        ];
    }

    public function forBusiness(Business $business): static
    {
        return $this->state(['business_id' => $business->id]);
    }

    public function createdBy(User $user): static
    {
        return $this->state(['created_by' => $user->id]);
    }
}
