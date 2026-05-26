<?php

use App\Models\Lead;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('starts with status new', function (): void {
    $lead = Lead::factory()->create(['status' => 'new']);
    expect($lead->status)->toBe('new');
});

it('transitions from new to contacted', function (): void {
    $lead = Lead::factory()->create(['status' => 'new']);
    $lead->transitionStatus('contacted');
    expect($lead->fresh()->status)->toBe('contacted');
});

it('rejects invalid status transition', function (): void {
    $lead = Lead::factory()->create(['status' => 'new']);
    expect(fn () => $lead->transitionStatus('replied'))
        ->toThrow(InvalidArgumentException::class);
});

it('scopes no website leads', function (): void {
    Lead::factory()->create(['website' => null]);
    Lead::factory()->withWebsite()->create();

    expect(Lead::noWebsite()->count())->toBe(1);
});

it('scopes has email leads', function (): void {
    Lead::factory()->create(['email' => 'test@example.com']);
    Lead::factory()->noEmail()->create();

    expect(Lead::whereNotNull('email')->count())->toBe(1);
});
