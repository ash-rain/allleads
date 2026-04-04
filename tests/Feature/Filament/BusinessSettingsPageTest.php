<?php

use App\Filament\Pages\BusinessSettings;
use App\Models\AiSetting;
use App\Models\BusinessSetting;
use App\Services\Intelligence\WebsiteScraper;
use Livewire\Livewire;

it('renders the business settings page', function (): void {
    actingAsAdmin();

    Livewire::test(BusinessSettings::class)
        ->assertSuccessful();
});

it('pre-fills form with existing business settings on mount', function (): void {
    actingAsAdmin();

    BusinessSetting::factory()->create([
        'business_name' => 'My Agency',
        'business_description' => 'We do great work.',
    ]);

    Livewire::test(BusinessSettings::class)
        ->assertSet('data.business_name', 'My Agency')
        ->assertSet('data.business_description', 'We do great work.');
});

it('pre-fills form with singleton defaults when no record exists', function (): void {
    actingAsAdmin();

    expect(BusinessSetting::count())->toBe(0);

    Livewire::test(BusinessSettings::class)
        ->assertSet('data.business_name', 'AllLeads Web Agency');

    expect(BusinessSetting::count())->toBe(1);
});

it('saves updated business settings to the database', function (): void {
    actingAsAdmin();

    BusinessSetting::factory()->create(['business_name' => 'Old Name']);

    Livewire::test(BusinessSettings::class)
        ->fillForm([
            'business_name' => 'New Name',
            'business_description' => 'Updated description.',
        ])
        ->call('save')
        ->assertNotified();

    expect(BusinessSetting::sole()->business_name)->toBe('New Name')
        ->and(BusinessSetting::sole()->business_description)->toBe('Updated description.');
});

it('requires business_name when saving', function (): void {
    actingAsAdmin();

    BusinessSetting::factory()->create();

    Livewire::test(BusinessSettings::class)
        ->fillForm(['business_name' => ''])
        ->call('save')
        ->assertHasFormErrors(['business_name' => 'required']);
});

it('requires business_description when saving', function (): void {
    actingAsAdmin();

    BusinessSetting::factory()->create();

    Livewire::test(BusinessSettings::class)
        ->fillForm(['business_description' => ''])
        ->call('save')
        ->assertHasFormErrors(['business_description' => 'required']);
});

it('generate from website fills the form with AI-parsed data', function (): void {
    actingAsAdmin();

    AiSetting::factory()->create();
    BusinessSetting::factory()->create();

    $this->app->bind(WebsiteScraper::class, function () {
        $mock = Mockery::mock(WebsiteScraper::class);
        $mock->shouldReceive('scrape')
            ->once()
            ->with('https://example.com')
            ->andReturn([
                'company_name' => 'Example Co',
                'tech_stack' => ['Laravel'],
                'pricing_tiers' => [],
                'job_postings' => [],
                'team_members' => [],
                'social_links' => [],
                'contact_info' => [],
                'company_size_signals' => null,
                'page_text' => 'We build great software for SMBs in the UK.',
            ]);

        return $mock;
    });

    fakeAiResponse(json_encode([
        'business_name' => 'Example Co',
        'industry' => 'Technology',
        'company_size' => '11-50',
        'year_founded' => '2018',
        'business_description' => 'We build great software.',
        'key_services' => 'SaaS, APIs',
        'unique_selling_points' => 'Fast and reliable',
        'target_audience' => 'SMBs',
        'geographic_focus' => 'UK',
        'value_proposition' => 'Save time with automation',
        'common_pain_points' => 'Manual processes',
        'call_to_action' => 'Book a demo',
        'social_proof' => '50+ happy clients',
    ]));

    Livewire::test(BusinessSettings::class)
        ->callAction('generate_from_website', ['url' => 'https://example.com'])
        ->assertSet('data.business_name', 'Example Co')
        ->assertSet('data.industry', 'Technology')
        ->assertSet('data.website_url', 'https://example.com')
        ->assertNotified();
});

it('generate from website shows notification on scraper failure', function (): void {
    actingAsAdmin();

    AiSetting::factory()->create();
    BusinessSetting::factory()->create();

    $this->app->bind(WebsiteScraper::class, function () {
        $mock = Mockery::mock(WebsiteScraper::class);
        $mock->shouldReceive('scrape')
            ->andThrow(new RuntimeException('Connection timed out.'));

        return $mock;
    });

    Livewire::test(BusinessSettings::class)
        ->callAction('generate_from_website', ['url' => 'https://example.com'])
        ->assertNotified();
});

it('page is not accessible to unauthenticated users', function (): void {
    $this->get('/app/business-settings')->assertRedirect();
});
