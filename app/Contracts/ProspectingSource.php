<?php

namespace App\Contracts;

use App\DataTransferObjects\ProspectingSearchCriteria;
use App\DataTransferObjects\ProspectingSearchResult;

interface ProspectingSource
{
    /** Unique identifier for this source, e.g. 'foursquare'. */
    public function identifier(): string;

    /** Human-readable name, e.g. 'Foursquare Places'. */
    public function name(): string;

    /** Whether this source requires a user-provided API key. */
    public function requiresApiKey(): bool;

    /** Search for businesses matching the given criteria. */
    public function search(ProspectingSearchCriteria $criteria): ProspectingSearchResult;
}
