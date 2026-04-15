<?php

namespace App\Ai\Tools\Email;

use App\Models\EmailDraft;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class FetchPreviousDrafts implements Tool
{
    public function description(): string
    {
        return 'Fetch the most recent email drafts written for a lead. Returns subject, body, and creation date for each draft. Useful for avoiding repetition and maintaining consistent tone across drafts.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'lead_id' => $schema
                ->integer()
                ->description('The ID of the lead whose email drafts should be fetched.')
                ->required(),
            'limit' => $schema
                ->integer()
                ->description('Maximum number of recent drafts to return.')
                ->default(3),
        ];
    }

    public function handle(Request $request): string
    {
        $leadId = (int) $request->get('lead_id');
        $limit = (int) $request->get('limit', 3);

        $drafts = EmailDraft::where('lead_id', $leadId)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get(['subject', 'body', 'created_at']);

        return json_encode($drafts->map(fn ($draft) => [
            'subject' => $draft->subject,
            'body' => $draft->body,
            'created_at' => $draft->created_at?->toIso8601String(),
        ])->values()->toArray());
    }
}
