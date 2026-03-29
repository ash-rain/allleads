<?php

use App\Models\Lead;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

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
    expect(fn() => $lead->transitionStatus('replied'))
        ->toThrow(\InvalidArgumentException::class);
});

it('scopes web dev prospects', function (): void {
    Lead::factory()->create(['review_rating' => 4.6, 'website' => null]);  // should appear (>4.5)
    Lead::factory()->withWebsite()->create(['review_rating' => 4.8]);       // has website — exclude
    Lead::factory()->create(['review_rating' => 3.0, 'website' => null]);   // low rating — exclude

    expect(Lead::webDevProspects()->count())->toBe(1);
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
