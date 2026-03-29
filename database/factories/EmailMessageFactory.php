<?php

namespace Database\Factories;

use App\Models\EmailMessage;
use App\Models\EmailThread;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmailMessage>
 */
class EmailMessageFactory extends Factory
{
    protected $model = EmailMessage::class;

    public function definition(): array
    {
        return [
            'thread_id' => EmailThread::factory(),
            'role' => 'outbound',
            'subject' => fake()->sentence(5),
            'body' => fake()->paragraphs(2, true),
            'message_id' => '<'.fake()->uuid().'@example.com>',
            'sender' => fake()->email(),
            'source' => 'brevo',
            'sent_at' => now(),
        ];
    }

    public function inbound(): static
    {
        return $this->state(['role' => 'lead_reply', 'source' => 'brevo_inbound']);
    }

    public function aiDraft(): static
    {
        return $this->state(['role' => 'ai_draft', 'source' => 'ai', 'sent_at' => null]);
    }
}
