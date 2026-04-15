<?php

namespace App\Ai\Tools\Email;

use App\Models\Lead;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class FetchLeadAnalyses implements Tool
{
    public function description(): string
    {
        return 'Fetch all completed AI analyses for a lead by their ID. Returns prospect analysis, website analysis, trend analysis, and geo analysis results. Missing or incomplete analyses are returned as null.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'lead_id' => $schema
                ->integer()
                ->description('The ID of the lead whose analyses should be fetched.')
                ->required(),
        ];
    }

    public function handle(Request $request): string
    {
        $leadId = (int) $request->get('lead_id');

        $lead = Lead::with([
            'prospectAnalysis',
            'websiteAnalysis',
            'trendAnalysis',
            'geoAnalysis',
        ])->find($leadId);

        if (! $lead) {
            return json_encode(['error' => "Lead #{$leadId} not found."]);
        }

        $prospect = $lead->prospectAnalysis;
        $website = $lead->websiteAnalysis;
        $trend = $lead->trendAnalysis;
        $geo = $lead->geoAnalysis;

        return json_encode([
            'prospect' => ($prospect && $prospect->status === 'completed') ? $prospect->result : null,
            'website' => ($website && $website->status === 'completed') ? $website->result : null,
            'trend' => ($trend && $trend->status === 'completed') ? $trend->result : null,
            'geo' => ($geo && $geo->status === 'completed') ? $geo->result : null,
        ]);
    }
}
