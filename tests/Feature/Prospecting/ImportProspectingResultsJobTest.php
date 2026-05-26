<?php

use App\Jobs\ImportProspectingResultsJob;
use App\Models\Business;
use App\Models\ImportBatch;
use App\Models\Lead;
use App\Models\ProspectingResult;
use App\Models\ProspectingSession;
use App\Models\Territory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::factory()->create();
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    $this->user->assignRole('admin');
    $this->business = Business::factory()->create();
    $this->business->users()->attach($this->user, ['role' => 'owner']);

    $this->territory = Territory::factory()->forBusiness($this->business)->create();
    $this->session = ProspectingSession::factory()
        ->forTerritory($this->territory)
        ->completed()
        ->create();
});

it('imports selected prospecting results into the leads table', function (): void {
    $results = ProspectingResult::factory()
        ->forSession($this->session)
        ->selected()
        ->count(3)
        ->create();

    ImportProspectingResultsJob::dispatchSync(
        sessionId: $this->session->id,
        resultIds: $results->pluck('id')->all(),
        assignTo: $this->user->id,
        tagIds: [],
        triggeredBy: $this->user->id,
    );

    // Should have created leads
    expect(Lead::count())->toBe(3);

    // Results should be marked as imported
    $results->each(fn ($result) => expect($result->fresh()->status)->toBe('imported'));

    // An import batch should have been created
    expect(ImportBatch::count())->toBe(1);
    expect(ImportBatch::first()->status)->toBe('completed');
});

it('creates an import batch with the session description', function (): void {
    $results = ProspectingResult::factory()
        ->forSession($this->session)
        ->selected()
        ->count(1)
        ->create();

    ImportProspectingResultsJob::dispatchSync(
        sessionId: $this->session->id,
        resultIds: $results->pluck('id')->all(),
        triggeredBy: $this->user->id,
    );

    $batch = ImportBatch::first();
    expect($batch->filename)->toContain('Prospecting:');
    expect($batch->filename)->toContain($this->session->search_query);
});

it('updates session imported count after import', function (): void {
    $results = ProspectingResult::factory()
        ->forSession($this->session)
        ->selected()
        ->count(5)
        ->create();

    ImportProspectingResultsJob::dispatchSync(
        sessionId: $this->session->id,
        resultIds: $results->pluck('id')->all(),
        triggeredBy: $this->user->id,
    );

    expect($this->session->fresh()->imported_count)->toBe(5);
});

it('skips results that are not selected or new', function (): void {
    $selected = ProspectingResult::factory()
        ->forSession($this->session)
        ->selected()
        ->create();

    $dismissed = ProspectingResult::factory()
        ->forSession($this->session)
        ->dismissed()
        ->create();

    ImportProspectingResultsJob::dispatchSync(
        sessionId: $this->session->id,
        resultIds: [$selected->id, $dismissed->id],
        triggeredBy: $this->user->id,
    );

    // Only the selected result should be imported
    expect($selected->fresh()->status)->toBe('imported');
    expect($dismissed->fresh()->status)->toBe('dismissed');
});
