<?php

use App\Filament\Resources\LeadResource\Pages\ListLeads;
use App\Filament\Resources\PlaybookResource\Pages\CreatePlaybook;
use App\Filament\Resources\PlaybookResource\Pages\EditPlaybook;
use App\Filament\Resources\PlaybookResource\Pages\ListPlaybooks;
use App\Models\Business;
use App\Models\Lead;
use App\Models\Playbook;
use Filament\Facades\Filament;
use Livewire\Livewire;

// ─── CRUD ─────────────────────────────────────────────────────────────────────

it('can list playbooks', function (): void {
    $admin = actingAsAdmin();
    $business = Business::factory()->create();
    $business->users()->attach($admin, ['role' => 'owner']);
    Filament::setTenant($business);

    $playbook = Playbook::factory()->forBusiness($business)->create(['name' => 'My Playbook']);

    Livewire::test(ListPlaybooks::class, ['tenant' => $business])
        ->assertCanSeeTableRecords([$playbook]);
});

it('can create a playbook', function (): void {
    $admin = actingAsAdmin();
    $business = Business::factory()->create();
    $business->users()->attach($admin, ['role' => 'owner']);
    Filament::setTenant($business);

    Livewire::test(CreatePlaybook::class, ['tenant' => $business])
        ->fillForm([
            'name' => 'Web Dev Prospects',
            'description' => 'No website, high rating',
            'sort_order' => 1,
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Playbook::where('business_id', $business->id)->where('name', 'Web Dev Prospects')->exists())->toBeTrue();
});

it('requires a name when creating a playbook', function (): void {
    $admin = actingAsAdmin();
    $business = Business::factory()->create();
    $business->users()->attach($admin, ['role' => 'owner']);
    Filament::setTenant($business);

    Livewire::test(CreatePlaybook::class, ['tenant' => $business])
        ->fillForm(['name' => ''])
        ->call('create')
        ->assertHasFormErrors(['name' => 'required']);
});

it('can edit a playbook name', function (): void {
    $admin = actingAsAdmin();
    $business = Business::factory()->create();
    $business->users()->attach($admin, ['role' => 'owner']);
    Filament::setTenant($business);

    $playbook = Playbook::factory()->forBusiness($business)->create(['name' => 'Old Name']);

    Livewire::test(EditPlaybook::class, ['record' => $playbook->id, 'tenant' => $business])
        ->fillForm(['name' => 'Updated Name'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($playbook->fresh()->name)->toBe('Updated Name');
});

it('can toggle a playbook inactive', function (): void {
    $admin = actingAsAdmin();
    $business = Business::factory()->create();
    $business->users()->attach($admin, ['role' => 'owner']);
    Filament::setTenant($business);

    $playbook = Playbook::factory()->forBusiness($business)->create(['is_active' => true]);

    Livewire::test(EditPlaybook::class, ['record' => $playbook->id, 'tenant' => $business])
        ->fillForm(['is_active' => false])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($playbook->fresh()->is_active)->toBeFalse();
});

it('only shows playbooks for the current business', function (): void {
    $admin = actingAsAdmin();
    $business = Business::factory()->create();
    $otherBusiness = Business::factory()->create();
    $business->users()->attach($admin, ['role' => 'owner']);
    Filament::setTenant($business);

    $myPlaybook = Playbook::factory()->forBusiness($business)->create();
    $otherPlaybook = Playbook::factory()->forBusiness($otherBusiness)->create();

    Livewire::test(ListPlaybooks::class, ['tenant' => $business])
        ->assertCanSeeTableRecords([$myPlaybook])
        ->assertCanNotSeeTableRecords([$otherPlaybook]);
});

// ─── HasPlaybooks on ListLeads ─────────────────────────────────────────────────

it('filters leads when a playbook is selected', function (): void {
    $admin = actingAsAdmin();
    $business = Business::factory()->create();
    $business->users()->attach($admin, ['role' => 'owner']);
    Filament::setTenant($business);

    $matchingLead = Lead::factory()->create([
        'business_id' => $business->id,
        'website' => null,
        'review_rating' => 4.8,
    ]);

    $nonMatchingLead = Lead::factory()->create([
        'business_id' => $business->id,
        'website' => 'https://example.com',
        'review_rating' => 4.8,
    ]);

    $playbook = Playbook::factory()->forBusiness($business)->withFilters([
        'no_website' => true,
    ])->create();

    Livewire::test(ListLeads::class, ['tenant' => $business])
        ->call('applyPlaybook', $playbook->id)
        ->assertCanSeeTableRecords([$matchingLead])
        ->assertCanNotSeeTableRecords([$nonMatchingLead]);
});

it('clears filter when playbook is set to null', function (): void {
    $admin = actingAsAdmin();
    $business = Business::factory()->create();
    $business->users()->attach($admin, ['role' => 'owner']);
    Filament::setTenant($business);

    $lead1 = Lead::factory()->create(['business_id' => $business->id, 'website' => null]);
    $lead2 = Lead::factory()->create(['business_id' => $business->id, 'website' => 'https://example.com']);

    $playbook = Playbook::factory()->forBusiness($business)->withFilters([
        'no_website' => true,
    ])->create();

    Livewire::test(ListLeads::class, ['tenant' => $business])
        ->call('applyPlaybook', $playbook->id)
        ->assertCanSeeTableRecords([$lead1])
        ->assertCanNotSeeTableRecords([$lead2])
        ->call('applyPlaybook', null)
        ->assertCanSeeTableRecords([$lead1, $lead2]);
});
