<?php

use App\Models\GeoAnalysis;
use App\Models\Lead;
use App\Models\LeadGeoAnalysis;
use App\Models\LeadProspectAnalysis;
use App\Models\LeadTrendAnalysis;
use App\Models\LeadWebsiteAnalysis;
use App\Models\TrendAnalysis;
use App\Models\User;

// ── Lead-scoped analysis PDF downloads ──────────────────────────────────────

it('downloads prospect analysis PDF for a lead', function (): void {
    actingAsAdmin();
    $analysis = LeadProspectAnalysis::factory()->create();

    $response = $this->get(route('intelligence.analysis.pdf', [
        'lead' => $analysis->lead_id,
        'type' => 'prospect',
    ]));

    $response->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

it('downloads website analysis PDF for a lead', function (): void {
    actingAsAdmin();
    $analysis = LeadWebsiteAnalysis::factory()->create();

    $response = $this->get(route('intelligence.analysis.pdf', [
        'lead' => $analysis->lead_id,
        'type' => 'website',
    ]));

    $response->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

it('downloads trend analysis PDF for a lead', function (): void {
    actingAsAdmin();
    $analysis = LeadTrendAnalysis::factory()->create();

    $response = $this->get(route('intelligence.analysis.pdf', [
        'lead' => $analysis->lead_id,
        'type' => 'trend',
    ]));

    $response->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

it('downloads geo analysis PDF for a lead', function (): void {
    actingAsAdmin();
    $analysis = LeadGeoAnalysis::factory()->create();

    $response = $this->get(route('intelligence.analysis.pdf', [
        'lead' => $analysis->lead_id,
        'type' => 'geo',
    ]));

    $response->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

it('returns 404 for invalid lead analysis type', function (): void {
    actingAsAdmin();
    $lead = Lead::factory()->create();

    $this->get(route('intelligence.analysis.pdf', ['lead' => $lead->id, 'type' => 'unknown']))
        ->assertNotFound();
});

it('returns 404 when lead analysis does not exist', function (): void {
    actingAsAdmin();
    $lead = Lead::factory()->create();

    $this->get(route('intelligence.analysis.pdf', ['lead' => $lead->id, 'type' => 'prospect']))
        ->assertNotFound();
});

it('returns 404 when lead analysis is pending', function (): void {
    actingAsAdmin();
    $analysis = LeadProspectAnalysis::factory()->pending()->create();

    $this->get(route('intelligence.analysis.pdf', ['lead' => $analysis->lead_id, 'type' => 'prospect']))
        ->assertNotFound();
});

it('returns 404 when lead analysis has failed', function (): void {
    actingAsAdmin();
    $analysis = LeadProspectAnalysis::factory()->failed()->create();

    $this->get(route('intelligence.analysis.pdf', ['lead' => $analysis->lead_id, 'type' => 'prospect']))
        ->assertNotFound();
});

it('redirects unauthenticated users from lead analysis PDF', function (): void {
    $lead = Lead::factory()->create();

    $this->get(route('intelligence.analysis.pdf', ['lead' => $lead->id, 'type' => 'prospect']))
        ->assertRedirect();
});

// ── Company-scoped analysis PDF downloads ───────────────────────────────────

it('downloads company trend analysis PDF', function (): void {
    $user = actingAsAdmin();
    $analysis = TrendAnalysis::factory()->create(['user_id' => $user->id]);

    $response = $this->get(route('business-intelligence.analysis.pdf', [
        'type' => 'trend',
        'id' => $analysis->id,
    ]));

    $response->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

it('downloads company geo analysis PDF', function (): void {
    $user = actingAsAdmin();
    $analysis = GeoAnalysis::factory()->create(['user_id' => $user->id]);

    $response = $this->get(route('business-intelligence.analysis.pdf', [
        'type' => 'geo',
        'id' => $analysis->id,
    ]));

    $response->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

it('returns 404 for invalid company analysis type', function (): void {
    actingAsAdmin();

    $this->get(route('business-intelligence.analysis.pdf', ['type' => 'prospect', 'id' => 1]))
        ->assertNotFound();
});

it('returns 404 when company analysis belongs to another user', function (): void {
    actingAsAdmin();
    $otherUser = User::factory()->create();
    $analysis = TrendAnalysis::factory()->create(['user_id' => $otherUser->id]);

    $this->get(route('business-intelligence.analysis.pdf', ['type' => 'trend', 'id' => $analysis->id]))
        ->assertNotFound();
});

it('returns 404 when company analysis is not completed', function (): void {
    $user = actingAsAdmin();
    $analysis = TrendAnalysis::factory()->pending()->create(['user_id' => $user->id]);

    $this->get(route('business-intelligence.analysis.pdf', ['type' => 'trend', 'id' => $analysis->id]))
        ->assertNotFound();
});

it('redirects unauthenticated users from company analysis PDF', function (): void {
    $this->get(route('business-intelligence.analysis.pdf', ['type' => 'trend', 'id' => 1]))
        ->assertRedirect();
});
