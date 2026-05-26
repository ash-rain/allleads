<?php

namespace App\DataTransferObjects;

class NormalisedBusiness
{
    public function __construct(
        public string $title,
        public string $source,
        public ?string $sourceId = null,
        public ?string $category = null,
        public ?string $address = null,
        public ?float $latitude = null,
        public ?float $longitude = null,
        public ?string $phone = null,
        public ?string $website = null,
        public ?string $email = null,
        public ?float $reviewRating = null,
        public ?int $reviewCount = null,
        public ?array $rawData = null,
    ) {}

    /**
     * Convert to an array suitable for creating a ProspectingResult.
     *
     * @return array<string, mixed>
     */
    public function toResultArray(): array
    {
        return [
            'source' => $this->source,
            'source_id' => $this->sourceId,
            'title' => $this->title,
            'category' => $this->category,
            'address' => $this->address,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'phone' => $this->phone,
            'website' => $this->website,
            'email' => $this->email,
            'review_rating' => $this->reviewRating,
            'review_count' => $this->reviewCount,
            'raw_data' => $this->rawData,
        ];
    }
}
