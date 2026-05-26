<?php

namespace App\Jobs;

use App\DataTransferObjects\ProspectingSearchCriteria;
use App\Models\ProspectingResult;
use App\Models\ProspectingSession;
use App\Services\Prospecting\SourceManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RunProspectingSearchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 120;

    public function __construct(
        public readonly int $sessionId,
    ) {}

    public function handle(SourceManager $sourceManager): void
    {
        $session = ProspectingSession::with('territory')->findOrFail($this->sessionId);

        $session->update(['status' => ProspectingSession::STATUS_SEARCHING]);

        try {
            $criteria = ProspectingSearchCriteria::fromSession($session);

            $searchResult = $sourceManager->search($session->business, $criteria);
            $businesses = $searchResult['businesses'];
            $sourcesUsed = $searchResult['sources_used'];

            Log::info("Prospecting session {$session->uuid}: found ".count($businesses).' results');

            // Create ProspectingResult records
            foreach ($businesses as $business) {
                $signals = $sourceManager->computeSignals($business, $session->business_id);
                $isDuplicate = in_array('already_a_lead', $signals);

                $resultData = $business->toResultArray();
                $resultData['prospecting_session_id'] = $session->id;
                $resultData['signals'] = $signals;
                $resultData['status'] = $isDuplicate
                    ? ProspectingResult::STATUS_DUPLICATE
                    : ProspectingResult::STATUS_NEW;

                ProspectingResult::create($resultData);
            }

            // Apply post-search filters from the session
            $this->applyFilters($session);

            $session->update([
                'status' => ProspectingSession::STATUS_COMPLETED,
                'sources_used' => $sourcesUsed,
                'searched_at' => now(),
            ]);

            $session->refreshCounts();
        } catch (\Throwable $e) {
            Log::error("Prospecting session {$session->uuid} failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $session->update(['status' => ProspectingSession::STATUS_FAILED]);

            throw $e;
        }
    }

    /**
     * Apply the session's filters to results (e.g. min/max rating, has_website).
     * Results that don't match are dismissed.
     */
    private function applyFilters(ProspectingSession $session): void
    {
        $filters = $session->filters ?? [];

        if (empty($filters)) {
            return;
        }

        $query = $session->results()->where('status', ProspectingResult::STATUS_NEW);

        if (isset($filters['min_rating'])) {
            $query->clone()
                ->whereNotNull('review_rating')
                ->where('review_rating', '<', $filters['min_rating'])
                ->update(['status' => ProspectingResult::STATUS_DISMISSED]);
        }

        if (isset($filters['max_rating'])) {
            $query->clone()
                ->whereNotNull('review_rating')
                ->where('review_rating', '>', $filters['max_rating'])
                ->update(['status' => ProspectingResult::STATUS_DISMISSED]);
        }

        if (isset($filters['has_website']) && $filters['has_website'] === true) {
            $session->results()
                ->where('status', ProspectingResult::STATUS_NEW)
                ->whereNull('website')
                ->update(['status' => ProspectingResult::STATUS_DISMISSED]);
        }

        if (isset($filters['has_website']) && $filters['has_website'] === false) {
            $session->results()
                ->where('status', ProspectingResult::STATUS_NEW)
                ->whereNotNull('website')
                ->update(['status' => ProspectingResult::STATUS_DISMISSED]);
        }

        if (isset($filters['has_phone']) && $filters['has_phone'] === true) {
            $session->results()
                ->where('status', ProspectingResult::STATUS_NEW)
                ->whereNull('phone')
                ->update(['status' => ProspectingResult::STATUS_DISMISSED]);
        }
    }

    public function failed(\Throwable $exception): void
    {
        $session = ProspectingSession::find($this->sessionId);
        $session?->update(['status' => ProspectingSession::STATUS_FAILED]);
    }
}
