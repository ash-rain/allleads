<?php

namespace App\Services\Prospecting;

use App\Contracts\ProspectingSource;
use App\DataTransferObjects\NormalisedBusiness;
use App\DataTransferObjects\ProspectingSearchCriteria;
use App\DataTransferObjects\ProspectingSearchResult;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenStreetMapSource implements ProspectingSource
{
    private const OVERPASS_URL = 'https://overpass-api.de/api/interpreter';

    /**
     * Common search terms mapped to OSM amenity/shop/office tags.
     *
     * @var array<string, array<string, string>>
     */
    private const CATEGORY_MAP = [
        'dentist' => ['amenity' => 'dentist'],
        'dental' => ['amenity' => 'dentist'],
        'doctor' => ['amenity' => 'doctors'],
        'pharmacy' => ['amenity' => 'pharmacy'],
        'restaurant' => ['amenity' => 'restaurant'],
        'cafe' => ['amenity' => 'cafe'],
        'pub' => ['amenity' => 'pub'],
        'bar' => ['amenity' => 'bar'],
        'hairdresser' => ['shop' => 'hairdresser'],
        'salon' => ['shop' => 'beauty'],
        'beauty' => ['shop' => 'beauty'],
        'gym' => ['leisure' => 'fitness_centre'],
        'fitness' => ['leisure' => 'fitness_centre'],
        'accountant' => ['office' => 'accountant'],
        'lawyer' => ['office' => 'lawyer'],
        'solicitor' => ['office' => 'lawyer'],
        'estate agent' => ['office' => 'estate_agent'],
        'real estate' => ['office' => 'estate_agent'],
        'insurance' => ['office' => 'insurance'],
        'plumber' => ['craft' => 'plumber'],
        'electrician' => ['craft' => 'electrician'],
        'carpenter' => ['craft' => 'carpenter'],
        'baker' => ['shop' => 'bakery'],
        'butcher' => ['shop' => 'butcher'],
        'supermarket' => ['shop' => 'supermarket'],
        'car repair' => ['shop' => 'car_repair'],
        'mechanic' => ['shop' => 'car_repair'],
        'garage' => ['shop' => 'car_repair'],
        'hotel' => ['tourism' => 'hotel'],
        'vet' => ['amenity' => 'veterinary'],
        'veterinary' => ['amenity' => 'veterinary'],
    ];

    public function identifier(): string
    {
        return 'osm';
    }

    public function name(): string
    {
        return 'OpenStreetMap';
    }

    public function requiresApiKey(): bool
    {
        return false;
    }

    public function search(ProspectingSearchCriteria $criteria): ProspectingSearchResult
    {
        $tag = $this->resolveTag($criteria->query);

        if (! $tag) {
            // Fall back to a name-based search across common amenity types
            return $this->searchByName($criteria);
        }

        $query = $this->buildOverpassQuery($tag, $criteria);

        return $this->executeQuery($query);
    }

    /**
     * @return array{string, string}|null
     */
    private function resolveTag(string $searchQuery): ?array
    {
        $normalised = strtolower(trim($searchQuery));

        // Direct match
        if (isset(self::CATEGORY_MAP[$normalised])) {
            return self::CATEGORY_MAP[$normalised];
        }

        // Partial match — find first category key that's contained in the query
        foreach (self::CATEGORY_MAP as $keyword => $tag) {
            if (str_contains($normalised, $keyword)) {
                return $tag;
            }
        }

        return null;
    }

    private function buildOverpassQuery(array $tag, ProspectingSearchCriteria $criteria): string
    {
        $key = array_key_first($tag);
        $value = $tag[$key];
        $radius = min($criteria->radiusMetres(), 50_000); // Overpass safe cap: 50km

        return <<<OVERPASS
        [out:json][timeout:30];
        (
            node["{$key}"="{$value}"](around:{$radius},{$criteria->latitude},{$criteria->longitude});
            way["{$key}"="{$value}"](around:{$radius},{$criteria->latitude},{$criteria->longitude});
        );
        out center body 200;
        OVERPASS;
    }

    private function searchByName(ProspectingSearchCriteria $criteria): ProspectingSearchResult
    {
        $escapedName = addslashes($criteria->query);
        $radius = $criteria->radiusMetres();

        $radius = min($criteria->radiusMetres(), 50_000); // Overpass safe cap: 50km

        $query = <<<OVERPASS
        [out:json][timeout:30];
        (
            node["name"~"{$escapedName}",i]["amenity"](around:{$radius},{$criteria->latitude},{$criteria->longitude});
            node["name"~"{$escapedName}",i]["shop"](around:{$radius},{$criteria->latitude},{$criteria->longitude});
            node["name"~"{$escapedName}",i]["office"](around:{$radius},{$criteria->latitude},{$criteria->longitude});
            way["name"~"{$escapedName}",i]["amenity"](around:{$radius},{$criteria->latitude},{$criteria->longitude});
            way["name"~"{$escapedName}",i]["shop"](around:{$radius},{$criteria->latitude},{$criteria->longitude});
            way["name"~"{$escapedName}",i]["office"](around:{$radius},{$criteria->latitude},{$criteria->longitude});
        );
        out center body 200;
        OVERPASS;

        return $this->executeQuery($query);
    }

    private function executeQuery(string $query): ProspectingSearchResult
    {
        $response = Http::timeout(35)
            ->retry(3, 5000, throw: false)
            ->withHeaders([
                'User-Agent' => 'AllLeads/1.0 (prospecting)',
            ])
            ->asForm()
            ->post(self::OVERPASS_URL, ['data' => $query]);

        if ($response->failed()) {
            Log::error('Overpass API error', [
                'status' => $response->status(),
                'body' => mb_substr($response->body(), 0, 500),
            ]);

            return new ProspectingSearchResult(businesses: [], totalFound: 0, source: $this->identifier());
        }

        $elements = $response->json('elements') ?? [];
        $businesses = [];

        foreach ($elements as $element) {
            $tags = $element['tags'] ?? [];

            // Skip elements without a name
            if (empty($tags['name'])) {
                continue;
            }

            $businesses[] = $this->normalise($element);
        }

        return new ProspectingSearchResult(
            businesses: $businesses,
            totalFound: count($businesses),
            source: $this->identifier(),
        );
    }

    private function normalise(array $element): NormalisedBusiness
    {
        $tags = $element['tags'] ?? [];

        // For ways, coordinates are in 'center'; for nodes, directly on the element
        $lat = $element['center']['lat'] ?? $element['lat'] ?? null;
        $lon = $element['center']['lon'] ?? $element['lon'] ?? null;

        $address = collect([
            $tags['addr:street'] ?? null,
            $tags['addr:housenumber'] ?? null,
            $tags['addr:city'] ?? null,
            $tags['addr:postcode'] ?? null,
        ])->filter()->implode(', ');

        // Determine category from OSM tags
        $category = $tags['amenity']
            ?? $tags['shop']
            ?? $tags['office']
            ?? $tags['craft']
            ?? $tags['leisure']
            ?? $tags['tourism']
            ?? null;

        return new NormalisedBusiness(
            title: $tags['name'],
            source: 'osm',
            sourceId: $element['type'].'/'.$element['id'],
            category: $category,
            address: $address ?: null,
            latitude: $lat ? (float) $lat : null,
            longitude: $lon ? (float) $lon : null,
            phone: $tags['phone'] ?? $tags['contact:phone'] ?? null,
            website: $tags['website'] ?? $tags['contact:website'] ?? null,
            email: $tags['email'] ?? $tags['contact:email'] ?? null,
            reviewRating: null, // OSM doesn't have ratings
            reviewCount: null,
            rawData: $element,
        );
    }
}
