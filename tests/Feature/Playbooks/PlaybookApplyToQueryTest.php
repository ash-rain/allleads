<?php

use App\Models\Business;
use App\Models\Lead;
use App\Models\Playbook;
use App\Models\Tag;

// ─── applyToQuery ──────────────────────────────────────────────────────────────

it('no_website filter returns leads without a website', function (): void {
    $business = Business::factory()->create();

    $noWebsite = Lead::factory()->create(['business_id' => $business->id, 'website' => null]);
    $hasWebsite = Lead::factory()->create(['business_id' => $business->id, 'website' => 'https://example.com']);

    $playbook = Playbook::factory()->forBusiness($business)->withFilters(['no_website' => true])->create();

    $results = $playbook->applyToQuery(Lead::where('business_id', $business->id))->pluck('id');

    expect($results)->toContain($noWebsite->id)
        ->not->toContain($hasWebsite->id);
});

it('has_email filter returns leads with an email', function (): void {
    $business = Business::factory()->create();

    $hasEmail = Lead::factory()->create(['business_id' => $business->id, 'email' => 'foo@example.com']);
    $noEmail = Lead::factory()->create(['business_id' => $business->id, 'email' => null]);

    $playbook = Playbook::factory()->forBusiness($business)->withFilters(['has_email' => true])->create();

    $results = $playbook->applyToQuery(Lead::where('business_id', $business->id))->pluck('id');

    expect($results)->toContain($hasEmail->id)
        ->not->toContain($noEmail->id);
});

it('rating_min filter returns leads at or above the threshold', function (): void {
    $business = Business::factory()->create();

    $highRating = Lead::factory()->create(['business_id' => $business->id, 'review_rating' => 4.5]);
    $lowRating = Lead::factory()->create(['business_id' => $business->id, 'review_rating' => 3.9]);

    $playbook = Playbook::factory()->forBusiness($business)->withFilters(['rating_min' => 4.0])->create();

    $results = $playbook->applyToQuery(Lead::where('business_id', $business->id))->pluck('id');

    expect($results)->toContain($highRating->id)
        ->not->toContain($lowRating->id);
});

it('categories filter returns leads matching given categories', function (): void {
    $business = Business::factory()->create();

    $restaurant = Lead::factory()->create(['business_id' => $business->id, 'category' => 'restaurant']);
    $cafe = Lead::factory()->create(['business_id' => $business->id, 'category' => 'cafe']);
    $gym = Lead::factory()->create(['business_id' => $business->id, 'category' => 'gym']);

    $playbook = Playbook::factory()->forBusiness($business)->withFilters([
        'categories' => ['restaurant', 'cafe'],
    ])->create();

    $results = $playbook->applyToQuery(Lead::where('business_id', $business->id))->pluck('id');

    expect($results)->toContain($restaurant->id)
        ->toContain($cafe->id)
        ->not->toContain($gym->id);
});

it('tags filter returns leads with matching tags', function (): void {
    $business = Business::factory()->create();

    $tag = Tag::factory()->create(['business_id' => $business->id, 'slug' => 'tag-a']);
    $otherTag = Tag::factory()->create(['business_id' => $business->id, 'slug' => 'tag-b']);

    $taggedLead = Lead::factory()->create(['business_id' => $business->id]);
    $taggedLead->tags()->attach($tag);

    $otherLead = Lead::factory()->create(['business_id' => $business->id]);
    $otherLead->tags()->attach($otherTag);

    $playbook = Playbook::factory()->forBusiness($business)->withFilters([
        'tags' => [$tag->id],
    ])->create();

    $results = $playbook->applyToQuery(Lead::where('business_id', $business->id))->pluck('id');

    expect($results)->toContain($taggedLead->id)
        ->not->toContain($otherLead->id);
});

it('combined filters apply all criteria', function (): void {
    $business = Business::factory()->create();

    $matching = Lead::factory()->create([
        'business_id' => $business->id,
        'website' => null,
        'review_rating' => 4.8,
    ]);

    $hasWebsite = Lead::factory()->create([
        'business_id' => $business->id,
        'website' => 'https://example.com',
        'review_rating' => 4.8,
    ]);

    $lowRating = Lead::factory()->create([
        'business_id' => $business->id,
        'website' => null,
        'review_rating' => 3.5,
    ]);

    $playbook = Playbook::factory()->forBusiness($business)->withFilters([
        'no_website' => true,
        'rating_min' => 4.5,
    ])->create();

    $results = $playbook->applyToQuery(Lead::where('business_id', $business->id))->pluck('id');

    expect($results)->toContain($matching->id)
        ->not->toContain($hasWebsite->id)
        ->not->toContain($lowRating->id);
});

it('empty filters returns all leads', function (): void {
    $business = Business::factory()->create();

    $lead1 = Lead::factory()->create(['business_id' => $business->id]);
    $lead2 = Lead::factory()->create(['business_id' => $business->id]);

    $playbook = Playbook::factory()->forBusiness($business)->withFilters([])->create();

    $results = $playbook->applyToQuery(Lead::where('business_id', $business->id))->pluck('id');

    expect($results)->toContain($lead1->id)
        ->toContain($lead2->id);
});

// ─── Seeded playbooks ──────────────────────────────────────────────────────────

it('Web Dev Prospects playbook returns no-website leads with rating >= 4.5', function (): void {
    $business = Business::factory()->create();

    $matching = Lead::factory()->create(['business_id' => $business->id, 'website' => null, 'review_rating' => 4.7]);
    $tooLow = Lead::factory()->create(['business_id' => $business->id, 'website' => null, 'review_rating' => 4.0]);
    $hasWebsite = Lead::factory()->create(['business_id' => $business->id, 'website' => 'https://x.com', 'review_rating' => 5.0]);

    $playbook = Playbook::factory()->forBusiness($business)->withFilters([
        'no_website' => true,
        'rating_min' => 4.5,
    ])->create(['name' => 'Web Dev Prospects']);

    $results = $playbook->applyToQuery(Lead::where('business_id', $business->id))->pluck('id');

    expect($results)->toContain($matching->id)
        ->not->toContain($tooLow->id)
        ->not->toContain($hasWebsite->id);
});

it('Contactable Leads playbook returns leads with email', function (): void {
    $business = Business::factory()->create();

    $contactable = Lead::factory()->create(['business_id' => $business->id, 'email' => 'a@example.com']);
    $notContactable = Lead::factory()->create(['business_id' => $business->id, 'email' => null]);

    $playbook = Playbook::factory()->forBusiness($business)->withFilters([
        'has_email' => true,
    ])->create(['name' => 'Contactable Leads']);

    $results = $playbook->applyToQuery(Lead::where('business_id', $business->id))->pluck('id');

    expect($results)->toContain($contactable->id)
        ->not->toContain($notContactable->id);
});
