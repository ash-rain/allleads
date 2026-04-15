<?php

use App\Filament\Pages\BusinessSettings;
use App\Models\AiSetting;
use App\Models\Business;
use App\Services\Intelligence\WebsiteScraper;
use Livewire\Livewire;

it('renders the business settings page', function (): void {
    $admin = actingAsAdmin();
    $business = Business::factory()->create();
    $business->users()->attach($admin, ['role' => 'owner']);

    Livewire::test(BusinessSettings::class, ['tenant' => $business->id])
        ->assertSuccessful();
});

it('pre-fills form with existing business settings on mount', function (): void {
    $admin = actingAsAdmin();

    $business = Business::factory()->create([
        'name' => 'My Agency',
        'description' => 'We do great work.',
    ]);
    $business->users()->attach($admin, ['role' => 'owner']);

    Livewire::test(BusinessSettings::class, ['tenant' => $business->id])
        ->assertSet('data.name', 'My Agency')
        ->assertSet('data.description', 'We do great work.');
});

it('pre-fills form with factory defaults on mount', function (): void {
    $admin = actingAsAdmin();
    $business = Business::factory()->create();
    $business->users()->attach($admin, ['role' => 'owner']);

    Livewire::test(BusinessSettings::class, ['tenant' => $business->id])
        ->assertSet('data.name', 'AllLeads Web Agency');
});

it('saves updated business settings to the database', function (): void {
    $admin = actingAsAdmin();

    $business = Business::factory()->create(['name' => 'Old Name']);
    $business->users()->attach($admin, ['role' => 'owner']);

    // Use ->set() on the statePath directly — fillForm() doesn't work reliably for
    // custom Filament pages (as opposed to resource edit pages).
    Livewire::test(BusinessSettings::class, ['tenant' => $business->id])
        ->set('data.name', 'New Name')
        ->set('data.description', 'Updated description.')
        ->call('save')
        ->assertNotified();

    $business->refresh();
    expect($business->name)->toBe('New Name')
        ->and($business->description)->toBe('Updated description.');
});

it('requires name when saving', function (): void {
    $admin = actingAsAdmin();

    $business = Business::factory()->create();
    $business->users()->attach($admin, ['role' => 'owner']);

    Livewire::test(BusinessSettings::class, ['tenant' => $business->id])
        ->set('data.name', '')
        ->call('save')
        ->assertHasFormErrors(['name' => 'required']);
});

it('requires description when saving', function (): void {
    $admin = actingAsAdmin();

    $business = Business::factory()->create();
    $business->users()->attach($admin, ['role' => 'owner']);

    Livewire::test(BusinessSettings::class, ['tenant' => $business->id])
        ->set('data.description', '')
        ->call('save')
        ->assertHasFormErrors(['description' => 'required']);
});

it('generate from website fills the form with AI-parsed data', function (): void {
    // TODO(phase-5): Migrate generateFromWebsite() to BusinessProfileAgent (new SDK).
    // The Filament callAction() test helper doesn't reliably pass data to the action's
    // modal form for custom pages. This test will be rewritten when the underlying
    // feature is migrated off AiProviderFactory.
    $this->markTestSkipped('generateFromWebsite uses legacy AiProviderFactory — migrating in Phase 5.');

    $admin = actingAsAdmin();

    $business = Business::factory()->create();
    $business->users()->attach($admin, ['role' => 'owner']);
    AiSetting::factory()->create(['business_id' => $business->id]);

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
        'name' => 'Example Co',
        'industry' => 'Technology',
        'company_size' => '11-50',
        'year_founded' => '2018',
        'description' => 'We build great software.',
        'key_services' => 'SaaS, APIs',
        'unique_selling_points' => 'Fast and reliable',
        'target_audience' => 'SMBs',
        'geographic_focus' => 'UK',
        'value_proposition' => 'Save time with automation',
        'common_pain_points' => 'Manual processes',
        'call_to_action' => 'Book a demo',
        'social_proof' => '50+ happy clients',
    ]));

    Livewire::test(BusinessSettings::class, ['tenant' => $business->id])
        ->callAction('generate_from_website', ['url' => 'https://example.com'])
        ->assertNotified(__('business.generated_success'))
        ->assertSet('data.name', 'Example Co')
        ->assertSet('data.industry', 'Technology')
        ->assertSet('data.website_url', 'https://example.com');
});

it('generate from website shows notification on scraper failure', function (): void {
    // TODO(phase-5): See above — same callAction() limitation for the modal form.
    $this->markTestSkipped('generateFromWebsite uses legacy AiProviderFactory — migrating in Phase 5.');

    $admin = actingAsAdmin();

    $business = Business::factory()->create();
    $business->users()->attach($admin, ['role' => 'owner']);
    AiSetting::factory()->create(['business_id' => $business->id]);

    $this->app->bind(WebsiteScraper::class, function () {
        $mock = Mockery::mock(WebsiteScraper::class);
        $mock->shouldReceive('scrape')
            ->andThrow(new RuntimeException('Connection timed out.'));

        return $mock;
    });

    Livewire::test(BusinessSettings::class, ['tenant' => $business->id])
        ->callAction('generate_from_website', ['url' => 'https://example.com'])
        ->assertNotified();
});

it('page is not accessible to unauthenticated users', function (): void {
    $business = Business::factory()->create();
    $this->get("/app/{$business->id}/business-settings")->assertRedirect();
});
