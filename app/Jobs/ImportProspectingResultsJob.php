<?php

namespace App\Jobs;

use App\Models\ImportBatch;
use App\Models\Lead;
use App\Models\ProspectingResult;
use App\Models\ProspectingSession;
use App\Services\Import\LeadImportPipeline;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class ImportProspectingResultsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 300;

    /**
     * @param  array<int>  $resultIds  IDs of ProspectingResult records to import
     * @param  array<int>  $tagIds  Tag IDs to attach to imported leads
     */
    public function __construct(
        public readonly int $sessionId,
        public readonly array $resultIds,
        public readonly ?int $assignTo = null,
        public readonly array $tagIds = [],
        public readonly ?int $triggeredBy = null,
    ) {}

    public function handle(LeadImportPipeline $pipeline): void
    {
        $session = ProspectingSession::findOrFail($this->sessionId);

        // Create an ImportBatch to track this import
        $batch = ImportBatch::create([
            'business_id' => $session->business_id,
            'uuid' => (string) Str::uuid(),
            'filename' => "Prospecting: {$session->search_query} in {$session->territory->name}",
            'status' => 'pending',
            'progress' => 0,
            'total' => 0,
            'created_count' => 0,
            'updated_count' => 0,
            'skipped_count' => 0,
            'failed_count' => 0,
            'created_by' => $this->triggeredBy,
        ]);

        // Convert prospecting results to the row format the pipeline expects
        $results = ProspectingResult::whereIn('id', $this->resultIds)
            ->where('prospecting_session_id', $this->sessionId)
            ->whereIn('status', [ProspectingResult::STATUS_SELECTED, ProspectingResult::STATUS_NEW])
            ->get();

        $rows = $results->map(fn (ProspectingResult $result): array => [
            'title' => $result->title,
            'category' => $result->category,
            'address' => $result->address,
            'phone' => $result->phone,
            'website' => $result->website,
            'email' => $result->email,
            'review_rating' => $result->review_rating,
        ])->all();

        // Run through the existing import pipeline
        $pipeline->process($rows, $batch, $this->assignTo, $this->tagIds);

        // Now link the prospecting results to their newly created leads
        foreach ($results as $result) {
            $lead = $this->findMatchingLead($result, $session->business_id);

            $result->update([
                'status' => ProspectingResult::STATUS_IMPORTED,
                'lead_id' => $lead?->id,
            ]);
        }

        $session->refreshCounts();
    }

    /** Find the lead that was created/updated from this prospecting result. */
    private function findMatchingLead(ProspectingResult $result, int $businessId): ?Lead
    {
        $query = Lead::where('business_id', $businessId);

        // Try phone match first
        if ($result->phone) {
            $match = (clone $query)->where('phone', $result->phone)->first();
            if ($match) {
                return $match;
            }
        }

        // Then title match
        return (clone $query)->where('title', $result->title)->latest()->first();
    }
}
