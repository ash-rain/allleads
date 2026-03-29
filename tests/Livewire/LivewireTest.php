<?php

use App\Livewire\DraftEditor;
use App\Livewire\ImportProgress;
use App\Livewire\LeadNotes;
use App\Models\EmailDraft;
use App\Models\EmailThread;
use App\Models\ImportBatch;
use App\Models\Lead;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
