<?php

use App\Models\GeoAnalysis;
use App\Models\Lead;
use App\Models\LeadGeoAnalysis;
use App\Models\LeadProspectAnalysis;
use App\Models\LeadTrendAnalysis;
use App\Models\LeadWebsiteAnalysis;
use App\Models\TrendAnalysis;
use App\Models\User;

const DOCX_CONTENT_TYPE = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';

// ── Lead-scoped analysis DOCX downloads ─────────────────────────────────────

it('downloads prospect analysis DOCX for a lead', function (): void {
    actingAsAdmin();
    $analysis = LeadProspectAnalysis::factory()->create();

    $response = $this->get(route('intelligence.analysis.docx', [
        'lead' => $analysis->lead_id,
        'type' => 'prospect',
    ]));

    $response->assertOk()
        ->assertHeader('content-type', DOCX_CONTENT_TYPE);
});

it('downloads website analysis DOCX for a lead', function (): void {
    actingAsAdmin();
    $analysis = LeadWebsiteAnalysis::factory()->create();

    $response = $this->get(route('intelligence.analysis.docx', [
        'lead' => $analysis->lead_id,
        'type' => 'website',
    ]));

    $response->assertOk()
        ->assertHeader('content-type', DOCX_CONTENT_TYPE);
});

it('downloads trend analysis DOCX for a lead', function (): void {
    actingAsAdmin();
    $analysis = LeadTrendAnalysis::factory()->create();

    $response = $this->get(route('intelligence.analysis.docx', [
        'lead' => $analysis->lead_id,
        'type' => 'trend',
    ]));

    $response->assertOk()
        ->assertHeader('content-type', DOCX_CONTENT_TYPE);
});

it('downloads geo analysis DOCX for a lead', function (): void {
    actingAsAdmin();
    $analysis = LeadGeoAnalysis::factory()->create();

    $response = $this->get(route('intelligence.analysis.docx', [
        'lead' => $analysis->lead_id,
        'type' => 'geo',
    ]));

    $response->assertOk()
        ->assertHeader('content-type', DOCX_CONTENT_TYPE);
});

it('returns 404 for invalid lead analysis type (docx)', function (): void {
    actingAsAdmin();
    $lead = Lead::factory()->create();

    $this->get(route('intelligence.analysis.docx', ['lead' => $lead->id, 'type' => 'unknown']))
        ->assertNotFound();
});

it('returns 404 when lead analysis does not exist (docx)', function (): void {
    actingAsAdmin();
    $lead = Lead::factory()->create();

    $this->get(route('intelligence.analysis.docx', ['lead' => $lead->id, 'type' => 'prospect']))
        ->assertNotFound();
});

it('returns 404 when lead analysis is pending (docx)', function (): void {
    actingAsAdmin();
    $analysis = LeadProspectAnalysis::factory()->pending()->create();

    $this->get(route('intelligence.analysis.docx', ['lead' => $analysis->lead_id, 'type' => 'prospect']))
        ->assertNotFound();
});

it('redirects unauthenticated users from lead analysis DOCX', function (): void {
    $lead = Lead::factory()->create();

    $this->get(route('intelligence.analysis.docx', ['lead' => $lead->id, 'type' => 'prospect']))
        ->assertRedirect();
});

// ── Company-scoped analysis DOCX downloads ───────────────────────────────────

it('downloads company trend analysis DOCX', function (): void {
    $user = actingAsAdmin();
    $analysis = TrendAnalysis::factory()->create(['user_id' => $user->id]);

    $response = $this->get(route('business-intelligence.analysis.docx', [
        'type' => 'trend',
        'id' => $analysis->id,
    ]));

    $response->assertOk()
        ->assertHeader('content-type', DOCX_CONTENT_TYPE);
});

it('downloads company geo analysis DOCX', function (): void {
    $user = actingAsAdmin();
    $analysis = GeoAnalysis::factory()->create(['user_id' => $user->id]);

    $response = $this->get(route('business-intelligence.analysis.docx', [
        'type' => 'geo',
        'id' => $analysis->id,
    ]));

    $response->assertOk()
        ->assertHeader('content-type', DOCX_CONTENT_TYPE);
});

it('returns 404 for invalid company analysis type (docx)', function (): void {
    actingAsAdmin();

    $this->get(route('business-intelligence.analysis.docx', ['type' => 'prospect', 'id' => 1]))
        ->assertNotFound();
});

it('returns 404 when company analysis belongs to another user (docx)', function (): void {
    actingAsAdmin();
    $other = User::factory()->create();
    $analysis = TrendAnalysis::factory()->create(['user_id' => $other->id]);

    $this->get(route('business-intelligence.analysis.docx', ['type' => 'trend', 'id' => $analysis->id]))
        ->assertNotFound();
});

it('returns 404 when company analysis is not completed (docx)', function (): void {
    $user = actingAsAdmin();
    $analysis = TrendAnalysis::factory()->pending()->create(['user_id' => $user->id]);

    $this->get(route('business-intelligence.analysis.docx', ['type' => 'trend', 'id' => $analysis->id]))
        ->assertNotFound();
});

it('redirects unauthenticated users from company analysis DOCX', function (): void {
    $this->get(route('business-intelligence.analysis.docx', ['type' => 'trend', 'id' => 1]))
        ->assertRedirect();
});
