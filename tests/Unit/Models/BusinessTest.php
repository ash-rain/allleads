<?php

use App\Models\AiSetting;
use App\Models\Business;
use App\Models\Lead;
use App\Models\User;

it('can be created with required fields', function (): void {
    $business = Business::factory()->create(['name' => 'Acme Corp']);

    expect($business->name)->toBe('Acme Corp');
});

it('has a many-to-many relationship with users', function (): void {
    $business = Business::factory()->create();
    $user = User::factory()->create();

    $business->users()->attach($user, ['role' => 'owner']);

    expect($business->users)->toHaveCount(1)
        ->and($business->users->first()->pivot->role)->toBe('owner');
});

it('has many leads', function (): void {
    $business = Business::factory()->create();
    Lead::factory()->count(3)->create(['business_id' => $business->id]);

    expect($business->leads)->toHaveCount(3);
});

it('isConfigured returns true when name and description are filled', function (): void {
    $business = Business::factory()->create([
        'name' => 'Acme Corp',
        'description' => 'We make great things.',
    ]);

    expect($business->isConfigured())->toBeTrue();
});

it('isConfigured returns false when description is empty', function (): void {
    $business = Business::factory()->create([
        'name' => 'Acme Corp',
        'description' => null,
    ]);

    expect($business->isConfigured())->toBeFalse();
});

it('toPromptContext returns fallback text when not configured', function (): void {
    // name has a NOT NULL constraint; description alone being null triggers isConfigured() === false
    $business = Business::factory()->create([
        'description' => null,
    ]);

    expect($business->toPromptContext())->toBe('You are targeting B2B prospects for outreach.');
});

it('toPromptContext includes name and key_services when configured', function (): void {
    $business = Business::factory()->create([
        'name' => 'Acme Corp',
        'description' => 'We build software.',
        'key_services' => 'SaaS, Mobile Apps',
        'call_to_action' => 'Book a demo',
    ]);

    $context = $business->toPromptContext();

    expect($context)
        ->toContain('Acme Corp')
        ->toContain('SaaS, Mobile Apps')
        ->toContain('Book a demo');
});

it('toPromptContext omits optional fields that are null', function (): void {
    $business = Business::factory()->create([
        'name' => 'Acme Corp',
        'description' => 'We build software.',
        'geographic_focus' => null,
        'social_proof' => null,
    ]);

    $context = $business->toPromptContext();

    expect($context)
        ->not->toContain('Geographic Focus')
        ->not->toContain('Social Proof');
});

it('aiSettingOrCreate returns the global singleton AI setting', function (): void {
    $business = Business::factory()->create();

    $aiSetting = $business->aiSettingOrCreate();

    expect($aiSetting)->toBeInstanceOf(AiSetting::class)
        ->and($aiSetting->id)->toBe(AiSetting::singleton()->id);
});
