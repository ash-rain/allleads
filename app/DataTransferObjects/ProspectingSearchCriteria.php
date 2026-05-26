<?php

namespace App\DataTransferObjects;

use App\Models\ProspectingSession;

class ProspectingSearchCriteria
{
    public function __construct(
        public string $query,
        public float $latitude,
        public float $longitude,
        public float $radiusKm,
        public ?float $minRating = null,
        public ?float $maxRating = null,
        public ?int $minReviews = null,
        public ?int $maxReviews = null,
        public ?bool $hasWebsite = null,
        public ?bool $hasPhone = null,
    ) {}

    /** Create from a ProspectingSession model. */
    public static function fromSession(ProspectingSession $session): self
    {
        $filters = $session->filters ?? [];

        return new self(
            query: $session->search_query,
            latitude: (float) $session->territory->latitude,
            longitude: (float) $session->territory->longitude,
            radiusKm: (float) $session->territory->radius_km,
            minRating: $filters['min_rating'] ?? null,
            maxRating: $filters['max_rating'] ?? null,
            minReviews: isset($filters['min_reviews']) ? (int) $filters['min_reviews'] : null,
            maxReviews: isset($filters['max_reviews']) ? (int) $filters['max_reviews'] : null,
            hasWebsite: $filters['has_website'] ?? null,
            hasPhone: $filters['has_phone'] ?? null,
        );
    }

    /** Radius in metres (useful for APIs that expect metres). */
    public function radiusMetres(): int
    {
        return (int) ($this->radiusKm * 1000);
    }
}
