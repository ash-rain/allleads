<?php

namespace Database\Factories;

use App\Models\EmailDraft;
use App\Models\EmailThread;
use App\Models\Lead;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmailDraft>
 */
class EmailDraftFactory extends Factory
{
    protected $model = EmailDraft::class;

    public function definition(): array
    {
        return [
            'lead_id'   => Lead::factory(),
            'thread_id' => EmailThread::factory(),
            'subject'   => fake()->sentence(5),
            'body'      => fake()->paragraphs(3, true),
            'status'    => 'pending',
            'version'   => 1,
        ];
    }

    public function sent(): static
    {
        return $this->state(['status' => 'sent']);
    }

    public function failed(string $error = 'API error'): static
    {
        return $this->state(['status' => 'failed', 'error' => $error]);
    }
}
