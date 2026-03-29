<?php

use App\Models\User;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature', 'Unit', 'Livewire');

/*
|--------------------------------------------------------------------------
| Custom expectations
|--------------------------------------------------------------------------
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Global helpers
|--------------------------------------------------------------------------
*/

/**
 * Create (or find) an admin user and authenticate as them.
 */
function actingAsAdmin(): User
{
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->assignRole('admin');
    test()->actingAs($user);
    return $user;
}

/**
 * Create (or find) an agent user and authenticate as them.
 */
function actingAsAgent(): User
{
    Role::firstOrCreate(['name' => 'agent', 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->assignRole('agent');
    test()->actingAs($user);
    return $user;
}

/**
 * Fake an AI provider response for the given text.
 * Mocks the HTTP client so no real API calls are made.
 */
function fakeAiResponse(string $text = 'Mocked AI response.'): void
{
    \Illuminate\Support\Facades\Http::fake([
        '*' => \Illuminate\Support\Facades\Http::response([
            'choices' => [
                ['message' => ['content' => $text]],
            ],
            // Gemini-style fallback:
            'candidates' => [
                ['content' => ['parts' => [['text' => $text]]]],
            ],
        ], 200),
    ]);
}

/**
 * Fake a successful Brevo send response returning a predictable message ID.
 */
function fakeBrevoResponse(string $messageId = '<fake-msg-id@brevo.example>'): void
{
    \Illuminate\Support\Facades\Http::fake([
        'api.brevo.com/*' => \Illuminate\Support\Facades\Http::response([
            'messageId' => $messageId,
        ], 201),
    ]);
}
