<?php

namespace Database\Factories;

use App\Models\ImportBatch;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lead>
 */
class LeadFactory extends Factory
{
    protected $model = Lead::class;

    public function definition(): array
    {
        return [
            'title' => fake()->company(),
            'category' => fake()->randomElement(['Restaurant', 'Café', 'Hotel', 'Shop', 'Clinic']),
            'address' => fake()->address(),
            'phone' => fake()->unique()->phoneNumber(),
            'website' => null,
            'email' => fake()->unique()->companyEmail(),
            'review_rating' => fake()->randomFloat(1, 2.0, 5.0),
            'status' => 'new',
            'source' => 'manual',
        ];
    }

    public function withWebsite(): static
    {
        return $this->state(['website' => fake()->url()]);
    }

    public function noEmail(): static
    {
        return $this->state(['email' => null]);
    }

    public function highRating(): static
    {
        return $this->state(['review_rating' => 4.5]);
    }

    public function status(string $status): static
    {
        return $this->state(['status' => $status]);
    }

    public function forBatch(ImportBatch $batch): static
    {
        return $this->state(['import_batch_id' => $batch->id]);
    }

    public function assignedTo(User $user): static
    {
        return $this->state(['assignee_id' => $user->id]);
    }
}
