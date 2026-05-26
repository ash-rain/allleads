<?php

use App\Models\Business;
use App\Models\ProspectingResult;
use App\Models\ProspectingSession;
use App\Models\Territory;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::factory()->create();
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    $this->user->assignRole('admin');
    $this->business = Business::factory()->create();
    $this->business->users()->attach($this->user, ['role' => 'owner']);
});

// ─── Territory ──────────────────────────────────────────────────────────────

it('creates a territory with correct attributes', function (): void {
    $territory = Territory::factory()->forBusiness($this->business)->create([
        'name' => 'Central Manchester',
        'latitude' => 53.4808,
        'longitude' => -2.2426,
        'radius_km' => 10.0,
    ]);

    expect($territory->name)->toBe('Central Manchester');
    expect((float) $territory->latitude)->toBe(53.4808);
    expect((float) $territory->radius_km)->toBe(10.0);
    expect($territory->business->id)->toBe($this->business->id);
});

it('generates a display label for a territory', function (): void {
    $territory = Territory::factory()->create([
        'name' => 'London Bridge',
        'radius_km' => 5.0,
    ]);

    expect($territory->displayLabel())->toBe('London Bridge (5.0km)');
});

it('enforces unique name per business on territories', function (): void {
    Territory::factory()->forBusiness($this->business)->create(['name' => 'Downtown']);

    expect(fn () => Territory::factory()->forBusiness($this->business)->create(['name' => 'Downtown']))
        ->toThrow(QueryException::class);
});

// ─── ProspectingSession ─────────────────────────────────────────────────────

it('auto-generates a UUID on creation', function (): void {
    $session = ProspectingSession::factory()->create();

    expect($session->uuid)->not->toBeNull();
    expect(strlen($session->uuid))->toBe(36);
});

it('casts filters and sources_used as arrays', function (): void {
    $session = ProspectingSession::factory()->completed()->create([
        'filters' => ['min_rating' => 3.0, 'has_website' => true],
        'sources_used' => ['foursquare', 'osm'],
    ]);

    $session->refresh();

    expect($session->filters)->toBeArray();
    expect($session->filters['min_rating'])->toBe(3.0);
    expect($session->sources_used)->toBe(['foursquare', 'osm']);
});

it('refreshes counts from results', function (): void {
    $session = ProspectingSession::factory()->completed()->create();

    ProspectingResult::factory()->forSession($session)->count(5)->create();
    ProspectingResult::factory()->forSession($session)->imported()->count(3)->create();
    ProspectingResult::factory()->forSession($session)->dismissed()->count(2)->create();

    $session->refreshCounts();

    expect($session->result_count)->toBe(10);
    expect($session->imported_count)->toBe(3);
    expect($session->dismissed_count)->toBe(2);
});

// ─── ProspectingResult ──────────────────────────────────────────────────────

it('belongs to a session', function (): void {
    $session = ProspectingSession::factory()->create();
    $result = ProspectingResult::factory()->forSession($session)->create();

    expect($result->session->id)->toBe($session->id);
});

it('returns signal descriptions', function (): void {
    $result = ProspectingResult::factory()->create([
        'signals' => ['no_website', 'low_reviews', 'already_a_lead'],
    ]);

    $descriptions = $result->signalDescriptions();

    expect($descriptions)->toContain('No website');
    expect($descriptions)->toContain('Low reviews (<20)');
    expect($descriptions)->toContain('Already a lead');
});

it('tracks selection status transitions', function (): void {
    $result = ProspectingResult::factory()->create();

    expect($result->status)->toBe(ProspectingResult::STATUS_NEW);

    $result->update(['status' => ProspectingResult::STATUS_SELECTED]);
    expect($result->fresh()->status)->toBe('selected');

    $result->update(['status' => ProspectingResult::STATUS_DISMISSED]);
    expect($result->fresh()->isDismissed())->toBeTrue();
});
