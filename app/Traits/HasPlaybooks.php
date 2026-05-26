<?php

namespace App\Traits;

use App\Models\Playbook;
use Illuminate\Database\Eloquent\Builder;

trait HasPlaybooks
{
    public ?int $selectedPlaybookId = null;

    /**
     * @return array<int|string, string>
     */
    public function getPlaybookOptions(): array
    {
        return Playbook::where('business_id', filament()->getTenant()->id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }

    public function applyPlaybook(?int $id): void
    {
        $this->selectedPlaybookId = $id;

        if (method_exists($this, 'resetPage')) {
            $this->resetPage();
        }
    }

    public function getTableQuery(): Builder
    {
        $query = parent::getTableQuery();

        if ($this->selectedPlaybookId) {
            $playbook = Playbook::find($this->selectedPlaybookId);
            $playbook?->applyToQuery($query);
        }

        return $query;
    }
}
