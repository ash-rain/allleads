<?php

namespace App\Filament\Widgets;

use App\Models\Lead;
use Filament\Widgets\ChartWidget;

class EmailFunnelChartWidget extends ChartWidget
{
    protected static ?string $heading = null;
    protected static ?int    $sort    = 7;

    public function getHeading(): ?string
    {
        return __('common.email_funnel_chart');
    }

    protected function getData(): array
    {
        $statuses = ['new', 'contacted', 'replied', 'closed', 'disqualified'];
        $counts   = Lead::selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $data   = array_map(fn($s) => (int) ($counts[$s] ?? 0), $statuses);
        $labels = array_map(fn($s) => __("leads.status_{$s}"), $statuses);

        $colors = ['#64748b', '#1e5a96', '#1e7896', '#22c55e', '#ef4444'];

        return [
            'datasets' => [
                [
                    'label'           => __('common.leads'),
                    'data'            => $data,
                    'backgroundColor' => $colors,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
