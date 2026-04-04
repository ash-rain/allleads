<?php

use App\Filament\Pages\AiSettings;
use App\Models\AiSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

it('renders the AI settings page', function (): void {
    actingAsAdmin();

    Livewire::test(AiSettings::class)
        ->assertSuccessful();
});

it('pre-fills form with existing API keys on mount', function (): void {
    actingAsAdmin();

    AiSetting::factory()->create([
        'openrouter_api_key' => 'sk-or-test-key',
        'groq_api_key' => 'gsk_test-key',
        'gemini_api_key' => null,
    ]);

    Livewire::test(AiSettings::class)
        ->assertSet('data.openrouter_api_key', 'sk-or-test-key')
        ->assertSet('data.groq_api_key', 'gsk_test-key')
        ->assertSet('data.gemini_api_key', null);
});

it('saves API keys encrypted to the database', function (): void {
    actingAsAdmin();
    Http::fake([
        'openrouter.ai/*' => Http::response(['data' => [['id' => 'nvidia/nemotron-3-super-120b-a12b:free']]], 200),
    ]);

    AiSetting::factory()->create();

    Livewire::test(AiSettings::class)
        ->set('data.openrouter_api_key', 'sk-or-secret')
        ->set('data.groq_api_key', 'gsk_secret')
        ->set('data.gemini_api_key', 'AIza_secret')
        ->call('save')
        ->assertNotified();

    $setting = AiSetting::sole();

    expect($setting->openrouter_api_key)->toBe('sk-or-secret')
        ->and($setting->groq_api_key)->toBe('gsk_secret')
        ->and($setting->gemini_api_key)->toBe('AIza_secret');

    // Verify the raw DB value is NOT the plaintext key (it's encrypted)
    $raw = DB::table('ai_settings')->value('openrouter_api_key');
    expect($raw)->not->toBe('sk-or-secret');
});

it('allows clearing an API key', function (): void {
    actingAsAdmin();
    Http::fake([
        'openrouter.ai/*' => Http::response(['data' => [['id' => 'nvidia/nemotron-3-super-120b-a12b:free']]], 200),
    ]);

    AiSetting::factory()->create(['openrouter_api_key' => 'sk-or-old-key']);

    Livewire::test(AiSettings::class)
        ->set('data.openrouter_api_key', '')
        ->call('save')
        ->assertNotified();

    expect(AiSetting::sole()->openrouter_api_key)->toBeNull();
});

it('apiKeyFor returns the correct key for each provider', function (): void {
    $setting = AiSetting::factory()->create([
        'openrouter_api_key' => 'sk-or-key',
        'groq_api_key' => 'gsk-key',
        'gemini_api_key' => 'AIza-key',
    ]);

    expect($setting->apiKeyFor('openrouter'))->toBe('sk-or-key')
        ->and($setting->apiKeyFor('groq'))->toBe('gsk-key')
        ->and($setting->apiKeyFor('gemini'))->toBe('AIza-key')
        ->and($setting->apiKeyFor('unknown'))->toBe('');
});

it('apiKeyFor returns empty string when keys are not set', function (): void {
    $setting = AiSetting::factory()->create([
        'openrouter_api_key' => null,
        'groq_api_key' => null,
        'gemini_api_key' => null,
    ]);

    expect($setting->apiKeyFor('openrouter'))->toBe('')
        ->and($setting->apiKeyFor('groq'))->toBe('')
        ->and($setting->apiKeyFor('gemini'))->toBe('');
});

it('testProvider sends a success notification when the API key is valid', function (): void {
    actingAsAdmin();

    Http::fake(['openrouter.ai/*' => Http::response(['data' => [['id' => 'nvidia/nemotron-3-super-120b-a12b:free']]], 200)]);

    AiSetting::factory()->create();

    Livewire::test(AiSettings::class)
        ->set('data.openrouter_api_key', 'sk-or-valid-key')
        ->call('testProvider', 'openrouter')
        ->assertNotified(__('ai.test_provider_success'));
});

it('testProvider sends a warning notification when no API key is configured', function (): void {
    actingAsAdmin();
    config()->set('ai.openrouter.api_key', '');

    AiSetting::factory()->create(['openrouter_api_key' => null]);

    Livewire::test(AiSettings::class)
        ->call('testProvider', 'openrouter')
        ->assertNotified(__('ai.test_provider_no_key'));
});

it('testProvider sends a danger notification when the API returns an error', function (): void {
    actingAsAdmin();

    // 401 on all calls: loadModels() will fall back to config, ping will fail
    Http::fake(['openrouter.ai/*' => Http::response(['error' => 'Unauthorized'], 401)]);

    AiSetting::factory()->create();

    Livewire::test(AiSettings::class)
        ->set('data.openrouter_api_key', 'sk-invalid-key')
        ->call('testProvider', 'openrouter')
        ->assertNotified(__('ai.test_provider_failed'));
});

it('testProvider sends a success notification for a valid Groq key', function (): void {
    actingAsAdmin();

    Http::fake(['api.groq.com/*' => Http::response(['object' => 'list', 'data' => []], 200)]);

    AiSetting::factory()->create();

    Livewire::test(AiSettings::class)
        ->set('data.groq_api_key', 'gsk_valid-key')
        ->call('testProvider', 'groq')
        ->assertNotified(__('ai.test_provider_success'));
});

it('testProvider sends a success notification for a valid Gemini key', function (): void {
    actingAsAdmin();

    Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['models' => []], 200)]);

    AiSetting::factory()->create();

    Livewire::test(AiSettings::class)
        ->set('data.gemini_api_key', 'AIza-valid-key')
        ->call('testProvider', 'gemini')
        ->assertNotified(__('ai.test_provider_success'));
});
