<?php

use App\Models\BusinessSetting;

it('singleton creates a record with defaults when none exists', function (): void {
    expect(BusinessSetting::count())->toBe(0);

    $setting = BusinessSetting::singleton();

    expect(BusinessSetting::count())->toBe(1)
        ->and($setting->business_name)->toBe('AllLeads Web Agency')
        ->and($setting->industry)->not->toBeNull();
});

it('singleton returns the same record on repeated calls', function (): void {
    $first = BusinessSetting::singleton();
    $second = BusinessSetting::singleton();

    expect(BusinessSetting::count())->toBe(1)
        ->and($first->id)->toBe($second->id);
});

it('isConfigured returns true when business_name and business_description are filled', function (): void {
    $setting = BusinessSetting::factory()->create([
        'business_name' => 'Acme Corp',
        'business_description' => 'We make great things.',
    ]);

    expect($setting->isConfigured())->toBeTrue();
});

it('isConfigured returns false when business_name is empty', function (): void {
    $setting = BusinessSetting::factory()->create([
        'business_name' => null,
        'business_description' => 'We make great things.',
    ]);

    expect($setting->isConfigured())->toBeFalse();
});

it('isConfigured returns false when business_description is empty', function (): void {
    $setting = BusinessSetting::factory()->create([
        'business_name' => 'Acme Corp',
        'business_description' => null,
    ]);

    expect($setting->isConfigured())->toBeFalse();
});

it('toPromptContext returns fallback text when not configured', function (): void {
    $setting = BusinessSetting::factory()->create([
        'business_name' => null,
        'business_description' => null,
    ]);

    expect($setting->toPromptContext())->toBe('You are targeting B2B prospects for outreach.');
});

it('toPromptContext includes business_name and key_services when configured', function (): void {
    $setting = BusinessSetting::factory()->create([
        'business_name' => 'Acme Corp',
        'business_description' => 'We build software.',
        'key_services' => 'SaaS, Mobile Apps',
        'call_to_action' => 'Book a demo',
    ]);

    $context = $setting->toPromptContext();

    expect($context)
        ->toContain('Acme Corp')
        ->toContain('SaaS, Mobile Apps')
        ->toContain('Book a demo');
});

it('toPromptContext omits optional fields that are null', function (): void {
    $setting = BusinessSetting::factory()->create([
        'business_name' => 'Acme Corp',
        'business_description' => 'We build software.',
        'geographic_focus' => null,
        'social_proof' => null,
    ]);

    $context = $setting->toPromptContext();

    expect($context)
        ->not->toContain('Geographic Focus')
        ->not->toContain('Social Proof');
});
