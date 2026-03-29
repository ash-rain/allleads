<?php

use App\Models\EmailDraft;
use App\Models\EmailThread;
use App\Models\Lead;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('saves a version when saveVersion is called', function (): void {
    $lead   = Lead::factory()->create();
    $thread = EmailThread::factory()->create(['lead_id' => $lead->id]);
    $draft  = EmailDraft::factory()->create([
        'lead_id'   => $lead->id,
        'thread_id' => $thread->id,
        'body'      => 'Original body',
        'version'   => 1,
    ]);

    $draft->saveVersion();

    expect($draft->versions()->count())->toBe(1)
        ->and($draft->version)->toBe(2);
});

it('restores a previous version', function (): void {
    $lead   = Lead::factory()->create();
    $thread = EmailThread::factory()->create(['lead_id' => $lead->id]);
    $draft  = EmailDraft::factory()->create([
        'lead_id'   => $lead->id,
        'thread_id' => $thread->id,
        'body'      => 'Version 1 body',
        'version'   => 1,
    ]);

    $draft->saveVersion();
    $draft->update(['body' => 'Version 2 body']);

    $draft->restoreVersion(1);

    expect($draft->fresh()->body)->toBe('Version 1 body');
});
