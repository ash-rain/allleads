<?php

namespace App\Livewire;

use App\Models\ImportBatch;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Polls the import_batches table for progress.
 * Used in the import modal and the ImportBatches list page.
 */
class ImportProgress extends Component
{
    public string $batchUuid;

    /** @var array<string, mixed>|null */
    public ?array $batch = null;

    protected $listeners = ['batchStarted' => 'load'];

    public function load(): void
    {
        $model = ImportBatch::where('uuid', $this->batchUuid)->first();

        if ($model) {
            $this->batch = [
                'uuid' => $model->uuid,
                'filename' => $model->filename,
                'status' => $model->status,
                'progress' => $model->progress,
                'total' => $model->total,
                'created_count' => $model->created_count,
                'updated_count' => $model->updated_count,
                'skipped_count' => $model->skipped_count,
                'failed_count' => $model->failed_count,
            ];
        }
    }

    public function render(): View
    {
        return view('livewire.import-progress');
    }
}
