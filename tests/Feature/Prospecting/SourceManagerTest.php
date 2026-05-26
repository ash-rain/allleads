<?php

use App\DataTransferObjects\NormalisedBusiness;
use App\Models\Business;
use App\Models\Lead;
use App\Models\User;
use App\Services\Prospecting\SourceManager;
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

it('deduplicates businesses by phone number', function (): void {
    $manager = new SourceManager;

    $a = new NormalisedBusiness(
        title: 'Acme Dentist',
        source: 'foursquare',
        sourceId: 'fsq_1',
        phone: '+44 161 123 4567',
        website: 'https://acme-dentist.co.uk',
    );

    $b = new NormalisedBusiness(
        title: 'Acme Dental Practice',
        source: 'osm',
        sourceId: 'node/12345',
        phone: '+44161 123 4567', // Same number, different formatting
    );

    // Use reflection to test the private method
    $reflection = new ReflectionClass($manager);
    $method = $reflection->getMethod('deduplicateBusinesses');
    $method->setAccessible(true);

    $result = $method->invoke($manager, collect([$a, $b]));

    expect($result)->toHaveCount(1);
    // Should keep the one with more data (has website)
    expect($result->first()->website)->toBe('https://acme-dentist.co.uk');
});

it('deduplicates businesses by name and proximity', function (): void {
    $manager = new SourceManager;

    $a = new NormalisedBusiness(
        title: 'Manchester Dental Care',
        source: 'foursquare',
        sourceId: 'fsq_2',
        latitude: 53.4808,
        longitude: -2.2426,
    );

    $b = new NormalisedBusiness(
        title: 'manchester dental care', // Same name, different case
        source: 'osm',
        sourceId: 'node/67890',
        latitude: 53.4809,  // ~10m away
        longitude: -2.2425,
    );

    $reflection = new ReflectionClass($manager);
    $method = $reflection->getMethod('deduplicateBusinesses');
    $method->setAccessible(true);

    $result = $method->invoke($manager, collect([$a, $b]));

    expect($result)->toHaveCount(1);
});

it('does not deduplicate businesses with different names at the same location', function (): void {
    $manager = new SourceManager;

    $a = new NormalisedBusiness(
        title: 'Acme Dentist',
        source: 'foursquare',
        sourceId: 'fsq_3',
        latitude: 53.4808,
        longitude: -2.2426,
    );

    $b = new NormalisedBusiness(
        title: 'Bob\'s Bakery',
        source: 'osm',
        sourceId: 'node/99999',
        latitude: 53.4809,
        longitude: -2.2425,
    );

    $reflection = new ReflectionClass($manager);
    $method = $reflection->getMethod('deduplicateBusinesses');
    $method->setAccessible(true);

    $result = $method->invoke($manager, collect([$a, $b]));

    expect($result)->toHaveCount(2);
});

it('computes signals correctly for a business without a website', function (): void {
    $manager = new SourceManager;

    $business = new NormalisedBusiness(
        title: 'No Website Business',
        source: 'foursquare',
        sourceId: 'fsq_4',
        reviewRating: 2.0,
        reviewCount: 5,
    );

    $signals = $manager->computeSignals($business, $this->business->id);

    expect($signals)->toContain('no_website');
    expect($signals)->toContain('low_rating');
    expect($signals)->toContain('low_reviews');
});

it('detects existing leads as duplicates in signals', function (): void {
    $manager = new SourceManager;

    Lead::factory()->forBusiness($this->business)->create([
        'phone' => '01onal234567',
        'title' => 'Existing Business',
    ]);

    $business = new NormalisedBusiness(
        title: 'Existing Business',
        source: 'foursquare',
        sourceId: 'fsq_5',
        phone: '01onal234567',
    );

    $signals = $manager->computeSignals($business, $this->business->id);

    expect($signals)->toContain('already_a_lead');
});

it('detects no_https signal', function (): void {
    $manager = new SourceManager;

    $business = new NormalisedBusiness(
        title: 'HTTP Business',
        source: 'foursquare',
        sourceId: 'fsq_6',
        website: 'http://old-website.com',
    );

    $signals = $manager->computeSignals($business, $this->business->id);

    expect($signals)->toContain('no_https');
    expect($signals)->not->toContain('no_website');
});
