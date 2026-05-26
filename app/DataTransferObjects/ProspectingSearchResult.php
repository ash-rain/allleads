<?php

namespace App\DataTransferObjects;

class ProspectingSearchResult
{
    /**
     * @param  array<NormalisedBusiness>  $businesses
     */
    public function __construct(
        public array $businesses,
        public int $totalFound,
        public string $source,
    ) {}
}
