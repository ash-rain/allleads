<?php

namespace App\Services\Prospecting;

use App\Contracts\ProspectingSource;
use App\DataTransferObjects\NormalisedBusiness;
use App\DataTransferObjects\ProspectingSearchCriteria;
use App\Models\Business;
use App\Models\Lead;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class SourceManager
{
    /** @var array<ProspectingSource> */
    private array $sources = [];

    public function __construct()
    {
        // Register default free sources
        $this->register(new FoursquareSource);
        $this->register(new OpenStreetMapSource);
    }

    public function register(ProspectingSource $source): void
    {
        $this->sources[$source->identifier()] = $source;
    }

    /**
     * Get sources enabled for the given business.
     *
     * @return array<ProspectingSource>
     */
    public function enabledSources(Business $business): array
    {
        return collect($this->sources)
            ->filter(function (ProspectingSource $source) {
                // Skip sources that need an API key if none is configured
                if (! $source->requiresApiKey()) {
                    return true;
                }

                // For BYOK sources, check if the business has configured a key
                // (future: check business settings for per-source API keys)
                return false;
            })
            ->values()
            ->all();
    }

    /**
     * Search across all enabled sources and return merged, deduplicated results.
     *
     * @return array{businesses: array<NormalisedBusiness>, sources_used: array<string>}
     */
    public function search(Business $business, ProspectingSearchCriteria $criteria): array
    {
        $allBusinesses = collect();
        $sourcesUsed = [];

        foreach ($this->enabledSources($business) as $source) {
            try {
                $result = $source->search($criteria);
                $allBusinesses = $allBusinesses->merge($result->businesses);
                $sourcesUsed[] = $source->identifier();

                Log::info("Prospecting: {$source->name()} returned {$result->totalFound} results");
            } catch (\Throwable $e) {
                Log::error("Prospecting source {$source->identifier()} failed", [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $deduplicated = $this->deduplicateBusinesses($allBusinesses);

        return [
            'businesses' => $deduplicated->values()->all(),
            'sources_used' => $sourcesUsed,
        ];
    }

    /**
     * Deduplicate businesses across sources.
     *
     * Matching criteria: exact phone match OR (title similarity > 85% AND coordinates within 100m).
     *
     * @param  Collection<int, NormalisedBusiness>  $businesses
     * @return Collection<int, NormalisedBusiness>
     */
    private function deduplicateBusinesses(Collection $businesses): Collection
    {
        $unique = collect();

        foreach ($businesses as $business) {
            $isDuplicate = false;

            foreach ($unique as $index => $existing) {
                if ($this->isDuplicate($existing, $business)) {
                    // Keep the record with more populated fields
                    if ($this->fieldCount($business) > $this->fieldCount($existing)) {
                        $unique[$index] = $business;
                    }
                    $isDuplicate = true;

                    break;
                }
            }

            if (! $isDuplicate) {
                $unique->push($business);
            }
        }

        return $unique;
    }

    private function isDuplicate(NormalisedBusiness $a, NormalisedBusiness $b): bool
    {
        // 1. Exact phone match (after normalisation)
        if ($a->phone && $b->phone) {
            $phoneA = preg_replace('/[^0-9+]/', '', $a->phone);
            $phoneB = preg_replace('/[^0-9+]/', '', $b->phone);

            if ($phoneA === $phoneB) {
                return true;
            }
        }

        // 2. Title similarity + coordinate proximity
        if ($a->latitude && $b->latitude && $a->longitude && $b->longitude) {
            $titleSimilarity = 0;
            similar_text(
                strtolower($a->title),
                strtolower($b->title),
                $titleSimilarity
            );

            if ($titleSimilarity > 85) {
                $distance = $this->haversineDistance(
                    $a->latitude,
                    $a->longitude,
                    $b->latitude,
                    $b->longitude,
                );

                if ($distance < 100) { // metres
                    return true;
                }
            }
        }

        return false;
    }

    /** Count populated fields on a NormalisedBusiness for richness comparison. */
    private function fieldCount(NormalisedBusiness $business): int
    {
        $count = 0;
        foreach (['category', 'address', 'phone', 'website', 'email', 'reviewRating', 'reviewCount'] as $field) {
            if ($business->$field !== null) {
                $count++;
            }
        }

        return $count;
    }

    /** Haversine distance between two coordinates, in metres. */
    private function haversineDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000; // metres

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2)
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
            * sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Compute quick-assessment signals for a discovered business.
     *
     * @return array<string>
     */
    public function computeSignals(NormalisedBusiness $business, int $businessId): array
    {
        $signals = [];

        if (empty($business->website)) {
            $signals[] = 'no_website';
        } elseif (str_starts_with($business->website, 'http://')) {
            $signals[] = 'no_https';
        }

        if ($business->reviewCount !== null && $business->reviewCount < 20) {
            $signals[] = 'low_reviews';
        }

        if ($business->reviewRating !== null && $business->reviewRating < 3.5) {
            $signals[] = 'low_rating';
        }

        // Check if this business is already in the leads table
        $isExistingLead = $this->isExistingLead($business, $businessId);
        if ($isExistingLead) {
            $signals[] = 'already_a_lead';
        }

        return $signals;
    }

    /** Check if a discovered business matches an existing lead. */
    private function isExistingLead(NormalisedBusiness $business, int $businessId): bool
    {
        $query = Lead::where('business_id', $businessId);

        // Phone match
        if ($business->phone) {
            $normalised = preg_replace('/[^0-9+]/', '', $business->phone);
            $match = (clone $query)->whereRaw(
                "REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '(', '') LIKE ?",
                ["%{$normalised}%"]
            )->exists();

            if ($match) {
                return true;
            }
        }

        // Title + address match
        if ($business->title && $business->address) {
            return (clone $query)
                ->where('title', $business->title)
                ->where('address', $business->address)
                ->exists();
        }

        return false;
    }
}
