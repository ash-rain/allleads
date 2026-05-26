<?php

namespace App\Services\Prospecting;

use App\Contracts\ProspectingSource;
use App\DataTransferObjects\NormalisedBusiness;
use App\DataTransferObjects\ProspectingSearchCriteria;
use App\DataTransferObjects\ProspectingSearchResult;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FoursquareSource implements ProspectingSource
{
    private const BASE_URL = 'https://api.foursquare.com/v3/places/search';

    private const MAX_RESULTS_PER_PAGE = 50;

    private const MAX_PAGES = 4; // 200 results max

    public function identifier(): string
    {
        return 'foursquare';
    }

    public function name(): string
    {
        return 'Foursquare Places';
    }

    public function requiresApiKey(): bool
    {
        return false; // Platform-level key, not BYOK
    }

    public function search(ProspectingSearchCriteria $criteria): ProspectingSearchResult
    {
        $apiKey = config('services.foursquare.api_key');

        if (empty($apiKey)) {
            Log::warning('Foursquare API key not configured');

            return new ProspectingSearchResult(businesses: [], totalFound: 0, source: $this->identifier());
        }

        $businesses = [];
        $cursor = null;

        for ($page = 0; $page < self::MAX_PAGES; $page++) {
            $params = [
                'query' => $criteria->query,
                'll' => "{$criteria->latitude},{$criteria->longitude}",
                'radius' => min($criteria->radiusMetres(), 100000), // Foursquare max 100km
                'limit' => self::MAX_RESULTS_PER_PAGE,
            ];

            if ($cursor) {
                $params['cursor'] = $cursor;
            }

            $response = Http::withHeaders([
                'Authorization' => $apiKey,
                'Accept' => 'application/json',
            ])->get(self::BASE_URL, $params);

            if ($response->failed()) {
                Log::error('Foursquare API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                break;
            }

            $data = $response->json();
            $results = $data['results'] ?? [];

            foreach ($results as $place) {
                $businesses[] = $this->normalise($place);
            }

            // Check for next page cursor
            $cursor = $data['context']['next_cursor'] ?? null;

            if (! $cursor || count($results) < self::MAX_RESULTS_PER_PAGE) {
                break;
            }
        }

        return new ProspectingSearchResult(
            businesses: $businesses,
            totalFound: count($businesses),
            source: $this->identifier(),
        );
    }

    private function normalise(array $place): NormalisedBusiness
    {
        $location = $place['location'] ?? [];
        $categories = $place['categories'] ?? [];

        $address = collect([
            $location['address'] ?? null,
            $location['locality'] ?? null,
            $location['postcode'] ?? null,
        ])->filter()->implode(', ');

        return new NormalisedBusiness(
            title: $place['name'] ?? 'Unknown',
            source: 'foursquare',
            sourceId: $place['fsq_id'] ?? null,
            category: ! empty($categories) ? $categories[0]['name'] ?? null : null,
            address: $address ?: null,
            latitude: isset($location['lat']) ? (float) $location['lat'] : null,
            longitude: isset($location['lng']) ? (float) $location['lng'] : null,
            phone: $place['tel'] ?? null,
            website: $place['website'] ?? null,
            email: $place['email'] ?? null,
            reviewRating: isset($place['rating']) ? round((float) $place['rating'] / 2, 1) : null, // Foursquare 0-10 → 0-5
            reviewCount: $place['stats']['total_ratings'] ?? null,
            rawData: $place,
        );
    }
}
