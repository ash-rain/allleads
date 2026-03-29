<?php

namespace App\Jobs;

use App\Models\ImportBatch;
use App\Services\Import\CsvLeadImporter;
use App\Services\Import\JsonLeadImporter;
use App\Services\Import\LeadImportPipeline;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;
use App\Models\User;
use App\Notifications\ImportCompletedNotification;

class ImportLeadsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 1;
    public int $timeout = 300;

    public function __construct(
        public readonly int    $batchId,
        public readonly string $filePath,
        public readonly string $mimeType,
        public readonly ?int   $assignTo   = null,
        public readonly array  $tagIds     = [],
        public readonly ?int   $triggeredBy = null,
    ) {}

    public function handle(
        CsvLeadImporter    $csvImporter,
        JsonLeadImporter   $jsonImporter,
        LeadImportPipeline $pipeline,
    ): void {
        $batch = ImportBatch::findOrFail($this->batchId);

        try {
            $rows = match (true) {
                str_contains($this->mimeType, 'json') => $jsonImporter->parse($this->filePath),
                default                               => $csvImporter->parse($this->filePath),
            };

            $pipeline->process($rows, $batch, $this->assignTo, $this->tagIds);
        } catch (\Throwable $e) {
            $batch->update(['status' => 'failed']);
            throw $e;
        } finally {
            // Clean up temporary upload
            if (file_exists($this->filePath)) {
                @unlink($this->filePath);
            }
        }

        // Notify the user who triggered the import
        if ($this->triggeredBy) {
            $user = User::find($this->triggeredBy);
            if ($user) {
                $user->notify(new ImportCompletedNotification($batch));
            }
        }
    }

    public function failed(\Throwable $exception): void
    {
        $batch = ImportBatch::find($this->batchId);
        $batch?->update(['status' => 'failed']);
    }
}
