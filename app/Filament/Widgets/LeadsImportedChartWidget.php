<?php

namespace App\Filament\Widgets;

use App\Models\Lead;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class LeadsImportedChartWidget extends ChartWidget
{
    protected ?string $heading  = null;
    protected static ?int    $sort     = 6;

    public function getHeading(): ?string
    {
        return __('common.leads_imported_chart');
    }

    protected function getData(): array
    {
        $weeks = collect(range(6, 0))->map(function (int $offset): array {
            $start = now()->startOfWeek()->subWeeks($offset);
            $end   = $start->copy()->endOfWeek();

            return [
                'label' => $start->format('d M'),
                'count' => Lead::whereBetween('created_at', [$start, $end])->count(),
            ];
        });

        return [
            'datasets' => [
                [
                    'label'           => __('common.leads'),
                    'data'            => $weeks->pluck('count')->toArray(),
                    'backgroundColor' => '#1e5a96',
                    'borderColor'     => '#1e5a96',
                ],
            ],
            'labels' => $weeks->pluck('label')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
