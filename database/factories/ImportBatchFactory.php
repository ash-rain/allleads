<?php

namespace Database\Factories;

use App\Models\ImportBatch;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ImportBatch>
 */
class ImportBatchFactory extends Factory
{
    protected $model = ImportBatch::class;

    public function definition(): array
    {
        return [
            'uuid'          => \Illuminate\Support\Str::uuid()->toString(),
            'filename'      => fake()->word() . '.csv',
            'status'        => 'completed',
            'total'         => 10,
            'created_count' => 8,
            'updated_count' => 1,
            'skipped_count' => 1,
            'failed_count'  => 0,
            'created_by'    => User::factory(),
        ];
    }

    public function pending(): static
    {
        return $this->state(['status' => 'pending', 'created_count' => 0, 'total' => 0]);
    }

    public function processing(): static
    {
        return $this->state(['status' => 'processing']);
    }
}
