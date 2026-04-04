<?php

namespace App\Livewire;

use App\Jobs\RunTrendAnalysisJob;
use App\Models\Lead;
use App\Models\LeadTrendAnalysis;
use Illuminate\View\View;
use Livewire\Component;

class TrendAnalysis extends Component
{
    public int $leadId;

    public ?string $analysisStatus = null;

    public string $topic = '';

    public function mount(): void
    {
        $analysis = LeadTrendAnalysis::where('lead_id', $this->leadId)->first();
        $this->analysisStatus = $analysis?->status;

        if (empty($this->topic)) {
            $lead = Lead::find($this->leadId);
            $this->topic = $analysis?->topic ?? trim(implode(' ', array_filter([
                $lead?->title,
                $lead?->category,
            ])));
        }
    }

    public function reload(): void
    {
        $this->analysisStatus = LeadTrendAnalysis::where('lead_id', $this->leadId)->value('status');
    }

    public function retry(): void
    {
        LeadTrendAnalysis::where('lead_id', $this->leadId)->update([
            'status' => LeadTrendAnalysis::STATUS_PENDING,
            'raw_data' => null,
            'result' => null,
            'error_message' => null,
            'started_at' => now(),
            'completed_at' => null,
        ]);

        RunTrendAnalysisJob::dispatch(
            Lead::findOrFail($this->leadId),
            auth()->id()
        );

        $this->analysisStatus = LeadTrendAnalysis::STATUS_PENDING;
    }

    public function runAnalysis(): void
    {
        $topicToUse = trim($this->topic);

        if (empty($topicToUse)) {
            $lead = Lead::findOrFail($this->leadId);
            $topicToUse = trim(implode(' ', array_filter([$lead->title, $lead->category])));
        }

        LeadTrendAnalysis::updateOrCreate(
            ['lead_id' => $this->leadId],
            [
                'topic' => $topicToUse,
                'status' => LeadTrendAnalysis::STATUS_PENDING,
                'raw_data' => null,
                'result' => null,
                'error_message' => null,
                'started_at' => now(),
                'completed_at' => null,
            ]
        );

        RunTrendAnalysisJob::dispatch(
            Lead::findOrFail($this->leadId),
            auth()->id()
        );

        $this->analysisStatus = LeadTrendAnalysis::STATUS_PENDING;
    }

    public function render(): View
    {
        $analysis = $this->analysisStatus
            ? LeadTrendAnalysis::where('lead_id', $this->leadId)->first()
            : null;

        return view('livewire.trend-analysis', [
            'analysis' => $analysis,
            'isPending' => $this->analysisStatus === LeadTrendAnalysis::STATUS_PENDING,
            'isCompleted' => $this->analysisStatus === LeadTrendAnalysis::STATUS_COMPLETED,
            'suggestions' => $this->getSuggestedTopics(),
        ]);
    }

    private function getSuggestedTopics(): array
    {
        $lead = Lead::find($this->leadId);

        if (! $lead) {
            return [];
        }

        return $this->buildLeadSuggestions($lead);
    }

    private function buildLeadSuggestions(Lead $lead): array
    {
        $suggestions = [];

        if (filled($lead->title)) {
            $suggestions[] = $lead->title.' market trends';
            $suggestions[] = $lead->title.' competitors';
        }

        if (filled($lead->category)) {
            $suggestions[] = $lead->category.' industry trends';
            $suggestions[] = $lead->category.' market analysis';
        }

        if (filled($lead->address)) {
            $parts = array_filter(array_map('trim', explode(',', $lead->address)));
            $location = end($parts) ?: null;
            if ($location && filled($lead->category)) {
                $suggestions[] = $lead->category.' market in '.$location;
            }
        }

        if (filled($lead->title)) {
            $suggestions[] = $lead->title.' growth opportunities';
        }

        return array_values(array_unique(array_slice($suggestions, 0, 6)));
    }
}
