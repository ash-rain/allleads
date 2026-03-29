<?php

namespace Database\Factories;

use App\Models\EmailThread;
use App\Models\Lead;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<EmailThread>
 */
class EmailThreadFactory extends Factory
{
    protected $model = EmailThread::class;

    public function definition(): array
    {
        return [
            'lead_id'    => Lead::factory(),
            'thread_key' => Str::uuid()->toString(),
            'status'     => 'open',
        ];
    }

    public function closed(): static
    {
        return $this->state(['status' => 'closed']);
    }
}
