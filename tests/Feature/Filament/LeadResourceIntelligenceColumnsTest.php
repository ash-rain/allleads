<?php

use App\Filament\Resources\LeadResource\Pages\ListLeads;
use App\Models\Lead;
use App\Models\LeadProspectAnalysis;
use App\Models\LeadWebsiteAnalysis;
use Livewire\Livewire;

// ─── Columns ──────────────────────────────────────────────────────────────────

it('shows prospect score badge when analysis is completed', function (): void {
    actingAsAdmin();

    $lead = Lead::factory()->create();
    LeadProspectAnalysis::factory()->create([
        'lead_id' => $lead->id,
        'status' => LeadProspectAnalysis::STATUS_COMPLETED,
        'result' => ['prospect_score' => 75, 'company_fit' => 'Good fit'],
    ]);

    Livewire::test(ListLeads::class)
        ->assertCanSeeTableRecords([$lead])
        ->assertSeeText('75');
});

it('shows website score badge when website analysis is completed', function (): void {
    actingAsAdmin();

    $lead = Lead::factory()->create(['website' => 'https://example.com']);
    LeadWebsiteAnalysis::factory()->create([
        'lead_id' => $lead->id,
        'status' => LeadWebsiteAnalysis::STATUS_COMPLETED,
        'result' => ['overall_score' => 82, 'business_overview' => 'A company.'],
    ]);

    Livewire::test(ListLeads::class)
        ->assertCanSeeTableRecords([$lead])
        ->assertSeeText('82');
});

it('shows avg intelligence score when both analyses are completed', function (): void {
    actingAsAdmin();

    $lead = Lead::factory()->create(['website' => 'https://example.com']);
    LeadProspectAnalysis::factory()->create([
        'lead_id' => $lead->id,
        'status' => LeadProspectAnalysis::STATUS_COMPLETED,
        'result' => ['prospect_score' => 80, 'company_fit' => 'Good fit'],
    ]);
    LeadWebsiteAnalysis::factory()->create([
        'lead_id' => $lead->id,
        'status' => LeadWebsiteAnalysis::STATUS_COMPLETED,
        'result' => ['overall_score' => 60, 'business_overview' => 'A company.'],
    ]);

    Livewire::test(ListLeads::class)
        ->assertCanSeeTableRecords([$lead])
        ->assertSeeText('70'); // avg of 80 and 60
});

it('shows avg score with only one analysis available', function (): void {
    actingAsAdmin();

    $lead = Lead::factory()->create();
    LeadProspectAnalysis::factory()->create([
        'lead_id' => $lead->id,
        'status' => LeadProspectAnalysis::STATUS_COMPLETED,
        'result' => ['prospect_score' => 90, 'company_fit' => 'Great fit'],
    ]);

    Livewire::test(ListLeads::class)
        ->assertCanSeeTableRecords([$lead])
        ->assertSeeText('90'); // avg of just the one score
});

// ─── Sorting ──────────────────────────────────────────────────────────────────

it('sorts by prospect score descending', function (): void {
    actingAsAdmin();

    $leadHigh = Lead::factory()->create();
    $leadLow = Lead::factory()->create();

    LeadProspectAnalysis::factory()->create([
        'lead_id' => $leadHigh->id,
        'status' => LeadProspectAnalysis::STATUS_COMPLETED,
        'result' => ['prospect_score' => 90],
    ]);
    LeadProspectAnalysis::factory()->create([
        'lead_id' => $leadLow->id,
        'status' => LeadProspectAnalysis::STATUS_COMPLETED,
        'result' => ['prospect_score' => 30],
    ]);

    Livewire::test(ListLeads::class)
        ->sortTable('prospect_score', 'desc')
        ->assertSeeText('90')
        ->assertSeeText('30');
});

it('sorts by website score descending', function (): void {
    actingAsAdmin();

    $leadHigh = Lead::factory()->create(['website' => 'https://a.com']);
    $leadLow = Lead::factory()->create(['website' => 'https://b.com']);

    LeadWebsiteAnalysis::factory()->create([
        'lead_id' => $leadHigh->id,
        'status' => LeadWebsiteAnalysis::STATUS_COMPLETED,
        'result' => ['overall_score' => 85],
    ]);
    LeadWebsiteAnalysis::factory()->create([
        'lead_id' => $leadLow->id,
        'status' => LeadWebsiteAnalysis::STATUS_COMPLETED,
        'result' => ['overall_score' => 25],
    ]);

    Livewire::test(ListLeads::class)
        ->sortTable('website_score', 'desc')
        ->assertSeeText('85')
        ->assertSeeText('25');
});

// ─── Filters ──────────────────────────────────────────────────────────────────

it('filters leads with no prospect analysis', function (): void {
    actingAsAdmin();

    $leadWithAnalysis = Lead::factory()->create();
    $leadWithout = Lead::factory()->create();

    LeadProspectAnalysis::factory()->create([
        'lead_id' => $leadWithAnalysis->id,
        'status' => LeadProspectAnalysis::STATUS_COMPLETED,
    ]);

    Livewire::test(ListLeads::class)
        ->filterTable('prospect_analysis_status', 'none')
        ->assertCanSeeTableRecords([$leadWithout])
        ->assertCanNotSeeTableRecords([$leadWithAnalysis]);
});

it('filters leads with completed prospect analysis', function (): void {
    actingAsAdmin();

    $leadCompleted = Lead::factory()->create();
    $leadPending = Lead::factory()->create();
    $leadNone = Lead::factory()->create();

    LeadProspectAnalysis::factory()->create([
        'lead_id' => $leadCompleted->id,
        'status' => LeadProspectAnalysis::STATUS_COMPLETED,
    ]);
    LeadProspectAnalysis::factory()->create([
        'lead_id' => $leadPending->id,
        'status' => LeadProspectAnalysis::STATUS_PENDING,
    ]);

    Livewire::test(ListLeads::class)
        ->filterTable('prospect_analysis_status', LeadProspectAnalysis::STATUS_COMPLETED)
        ->assertCanSeeTableRecords([$leadCompleted])
        ->assertCanNotSeeTableRecords([$leadPending, $leadNone]);
});

it('filters leads with no website analysis', function (): void {
    actingAsAdmin();

    $leadWithAnalysis = Lead::factory()->create(['website' => 'https://a.com']);
    $leadWithout = Lead::factory()->create(['website' => 'https://b.com']);

    LeadWebsiteAnalysis::factory()->create([
        'lead_id' => $leadWithAnalysis->id,
        'status' => LeadWebsiteAnalysis::STATUS_COMPLETED,
    ]);

    Livewire::test(ListLeads::class)
        ->filterTable('website_analysis_status', 'none')
        ->assertCanSeeTableRecords([$leadWithout])
        ->assertCanNotSeeTableRecords([$leadWithAnalysis]);
});

it('filters leads with completed website analysis', function (): void {
    actingAsAdmin();

    $leadCompleted = Lead::factory()->create(['website' => 'https://a.com']);
    $leadFailed = Lead::factory()->create(['website' => 'https://b.com']);
    $leadNone = Lead::factory()->create(['website' => 'https://c.com']);

    LeadWebsiteAnalysis::factory()->create([
        'lead_id' => $leadCompleted->id,
        'status' => LeadWebsiteAnalysis::STATUS_COMPLETED,
    ]);
    LeadWebsiteAnalysis::factory()->create([
        'lead_id' => $leadFailed->id,
        'status' => LeadWebsiteAnalysis::STATUS_FAILED,
    ]);

    Livewire::test(ListLeads::class)
        ->filterTable('website_analysis_status', LeadWebsiteAnalysis::STATUS_COMPLETED)
        ->assertCanSeeTableRecords([$leadCompleted])
        ->assertCanNotSeeTableRecords([$leadFailed, $leadNone]);
});
