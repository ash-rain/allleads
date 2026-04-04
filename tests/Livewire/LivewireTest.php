<?php

use App\Jobs\RunProspectAnalysisJob;
use App\Jobs\RunTrendAnalysisJob;
use App\Livewire\DraftEditor;
use App\Livewire\ImportProgress;
use App\Livewire\IntelligenceDashboard;
use App\Livewire\LeadNotes;
use App\Livewire\ProspectAnalysis;
use App\Livewire\TrendAnalysis;
use App\Models\EmailDraft;
use App\Models\EmailThread;
use App\Models\ImportBatch;
use App\Models\Lead;
use App\Models\LeadProspectAnalysis;
use App\Models\LeadTrendAnalysis;
use App\Models\LeadWebsiteAnalysis;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

uses(RefreshDatabase::class);

// ─── LeadNotes ───────────────────────────────────────────────────────────────

it('can add a note to a lead', function (): void {
    actingAsAdmin();
    $lead = Lead::factory()->create();

    Livewire::test(LeadNotes::class, ['leadId' => $lead->id])
        ->set('body', 'This is a test note.')
        ->call('addNote')
        ->assertSet('body', '');

    expect($lead->notes()->count())->toBe(1);
});

it('can add a call note to a lead', function (): void {
    actingAsAdmin();
    $lead = Lead::factory()->create();

    Livewire::test(LeadNotes::class, ['leadId' => $lead->id])
        ->set('type', 'call')
        ->set('body', 'Called them.')
        ->set('duration', 10)
        ->set('outcome', 'interested')
        ->call('addNote');

    $note = $lead->notes()->first();
    expect($note->type)->toBe('call');
    expect($note->duration)->toBe(10);
});

// ─── ImportProgress ──────────────────────────────────────────────────────────

it('polls and displays batch progress', function (): void {
    actingAsAdmin();
    $batch = ImportBatch::factory()->create([
        'status' => 'processing',
        'total' => 10,
        'created_count' => 5,
    ]);

    Livewire::test(ImportProgress::class, ['batchUuid' => $batch->uuid ?? $batch->id])
        ->assertSee('5');
});

// ─── DraftEditor ─────────────────────────────────────────────────────────────

it('saves edited draft body', function (): void {
    actingAsAdmin();
    $lead = Lead::factory()->create();
    $thread = EmailThread::factory()->create(['lead_id' => $lead->id]);
    $draft = EmailDraft::factory()->create([
        'lead_id' => $lead->id,
        'thread_id' => $thread->id,
        'subject' => 'Original Subject',
        'body' => 'Original body',
    ]);

    Livewire::test(DraftEditor::class, ['draftId' => $draft->id])
        ->set('subject', 'New Subject')
        ->set('body', 'Updated body')
        ->call('save');

    expect($draft->fresh()->subject)->toBe('New Subject')
        ->and($draft->fresh()->body)->toBe('Updated body');
});

// ─── ProspectAnalysis ────────────────────────────────────────────────────────

it('ProspectAnalysis renders empty state when no analysis exists', function (): void {
    actingAsAdmin();
    $lead = Lead::factory()->create();

    Livewire::test(ProspectAnalysis::class, ['leadId' => $lead->id])
        ->assertSet('analysis', null)
        ->assertSee(__('leads.analysis_no_data'));
});

it('ProspectAnalysis shows pending state', function (): void {
    actingAsAdmin();
    $lead = Lead::factory()->create();

    LeadProspectAnalysis::factory()->pending()->create(['lead_id' => $lead->id]);

    Livewire::test(ProspectAnalysis::class, ['leadId' => $lead->id])
        ->assertSee(__('leads.analysis_pending'));
});

it('ProspectAnalysis shows completed analysis result', function (): void {
    actingAsAdmin();
    $lead = Lead::factory()->create();

    LeadProspectAnalysis::factory()->create([
        'lead_id' => $lead->id,
        'result' => [
            'prospect_score' => 82,
            'company_fit' => 'Excellent prospect.',
            'contact_intel' => 'Owner-operated.',
            'opportunity' => 'No website.',
            'competitive_intel' => 'None found.',
            'outreach_strategy' => 'Lead with ROI.',
        ],
    ]);

    Livewire::test(ProspectAnalysis::class, ['leadId' => $lead->id])
        ->assertSee('82')
        ->assertSee('Excellent prospect.');
});

it('ProspectAnalysis retry dispatches job', function (): void {
    Queue::fake();
    actingAsAdmin();
    $lead = Lead::factory()->create();

    LeadProspectAnalysis::factory()->failed()->create(['lead_id' => $lead->id]);

    Livewire::test(ProspectAnalysis::class, ['leadId' => $lead->id])
        ->call('retry');

    Queue::assertPushed(RunProspectAnalysisJob::class);
});

// ─── IntelligenceDashboard ───────────────────────────────────────────────────

it('IntelligenceDashboard shows prospect score when analysis is completed', function (): void {
    actingAsAdmin();
    $lead = Lead::factory()->create();

    LeadProspectAnalysis::factory()->create([
        'lead_id' => $lead->id,
        'result' => [
            'prospect_score' => 75,
            'company_fit' => 'Good fit.',
            'contact_intel' => 'Owner.',
            'opportunity' => 'No website.',
            'competitive_intel' => 'None.',
            'outreach_strategy' => 'Lead with value.',
        ],
    ]);

    Livewire::test(IntelligenceDashboard::class, ['leadId' => $lead->id])
        ->assertSee('75/100');
});

it('IntelligenceDashboard shows website score when analysis is completed', function (): void {
    actingAsAdmin();
    $lead = Lead::factory()->create();

    LeadWebsiteAnalysis::factory()->create([
        'lead_id' => $lead->id,
        'result' => array_merge(LeadWebsiteAnalysis::factory()->make()->result, ['overall_score' => 68]),
    ]);

    Livewire::test(IntelligenceDashboard::class, ['leadId' => $lead->id])
        ->assertSee('68/100');
});

it('IntelligenceDashboard renders cards without scores when no analyses exist', function (): void {
    actingAsAdmin();
    $lead = Lead::factory()->create();

    Livewire::test(IntelligenceDashboard::class, ['leadId' => $lead->id])
        ->assertDontSee('/100')
        ->assertSee(__('leads.prospect_analysis'))
        ->assertSee(__('leads.website_analysis'));
});

// ─── TrendAnalysis ───────────────────────────────────────────────────────────

it('TrendAnalysis shows empty state when no analysis exists', function (): void {
    actingAsAdmin();
    $lead = Lead::factory()->create(['title' => 'Cafe Bella', 'category' => 'Restaurant']);

    Livewire::test(TrendAnalysis::class, ['leadId' => $lead->id])
        ->assertSee(__('leads.no_analysis_yet'))
        ->assertSee(__('leads.run_analysis'));
});

it('TrendAnalysis shows pending state', function (): void {
    actingAsAdmin();
    $lead = Lead::factory()->create();

    LeadTrendAnalysis::factory()->pending()->create(['lead_id' => $lead->id]);

    Livewire::test(TrendAnalysis::class, ['leadId' => $lead->id])
        ->assertSee(__('leads.analysis_in_progress'));
});

it('TrendAnalysis shows completed analysis result', function (): void {
    actingAsAdmin();
    $lead = Lead::factory()->create();

    LeadTrendAnalysis::factory()->create([
        'lead_id' => $lead->id,
        'result' => [
            'market_overview' => 'Market is growing fast.',
            'trending_topics' => ['POS systems'],
            'community_sentiment' => 'Positive.',
            'opportunities' => ['Sell POS'],
            'talking_points' => ['70% upgraded POS', 'Save costs', 'Competitors online'],
            'prediction_markets' => null,
            'relevance_score' => 85,
        ],
    ]);

    Livewire::test(TrendAnalysis::class, ['leadId' => $lead->id])
        ->assertSee('85')
        ->assertSee('Market is growing fast.');
});

it('TrendAnalysis runAnalysis dispatches job', function (): void {
    Queue::fake();
    actingAsAdmin();
    $lead = Lead::factory()->create(['title' => 'Test Co', 'category' => 'Tech']);

    Livewire::test(TrendAnalysis::class, ['leadId' => $lead->id])
        ->set('topic', 'tech industry trends')
        ->call('runAnalysis');

    Queue::assertPushed(RunTrendAnalysisJob::class);
});

it('TrendAnalysis retry dispatches job', function (): void {
    Queue::fake();
    actingAsAdmin();
    $lead = Lead::factory()->create();

    LeadTrendAnalysis::factory()->failed()->create(['lead_id' => $lead->id]);

    Livewire::test(TrendAnalysis::class, ['leadId' => $lead->id])
        ->call('retry');

    Queue::assertPushed(RunTrendAnalysisJob::class);
});

it('IntelligenceDashboard shows trend analysis score when completed', function (): void {
    actingAsAdmin();
    $lead = Lead::factory()->create();

    LeadTrendAnalysis::factory()->create([
        'lead_id' => $lead->id,
        'result' => [
            'market_overview' => 'Growing market.',
            'trending_topics' => ['AI tools'],
            'community_sentiment' => 'Positive.',
            'opportunities' => ['Sell AI'],
            'talking_points' => ['Point A', 'Point B', 'Point C'],
            'prediction_markets' => null,
            'relevance_score' => 88,
        ],
    ]);

    Livewire::test(IntelligenceDashboard::class, ['leadId' => $lead->id])
        ->assertSee('88');
});
