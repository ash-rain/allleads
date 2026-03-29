<?php

use App\Services\Import\LeadImportPipeline;
use App\Models\ImportBatch;
use App\Models\Lead;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('creates new leads from valid rows', function (): void {
    $user  = User::factory()->create();
    $batch = ImportBatch::factory()->pending()->create();

    $rows = [
        [
            'title'         => 'Acme Corp',
            'category'      => 'Tech',
            'address'       => '1 Main St',
            'phone'         => '0888000001',
            'email'         => 'acme@example.com',
            'review_rating' => '4.5',
        ],
    ];

    $pipeline = app(LeadImportPipeline::class);
    $pipeline->process($rows, $batch, $user->id, []);

    expect(Lead::where('title', 'Acme Corp')->exists())->toBeTrue();
    expect($batch->fresh()->status)->toBe('completed');
});

it('updates an existing lead on duplicate phone', function (): void {
    $user  = User::factory()->create();
    $batch = ImportBatch::factory()->pending()->create();

    Lead::factory()->create([
        'title'  => 'Original Name',
        'phone'  => '0888111222',
        'email'  => null,
    ]);

    $rows = [
        [
            'title'  => 'Updated Name',
            'phone'  => '0888111222',
            'email'  => 'new@example.com',
        ],
    ];

    $pipeline = app(LeadImportPipeline::class);
    $pipeline->process($rows, $batch, $user->id, []);

    $lead = Lead::where('phone', '0888111222')->first();
    expect($lead->email)->toBe('new@example.com');
    expect($batch->fresh()->updated_count)->toBe(1);
});

it('fails rows with an invalid email gracefully', function (): void {
    $user  = User::factory()->create();
    $batch = ImportBatch::factory()->pending()->create();

    $rows = [
        ['title' => 'Bad Lead', 'email' => 'not-an-email'],
    ];

    $pipeline = app(LeadImportPipeline::class);
    $pipeline->process($rows, $batch, $user->id, []);

    expect(Lead::where('title', 'Bad Lead')->exists())->toBeFalse();
    expect($batch->fresh()->failed_count)->toBe(1);
});
