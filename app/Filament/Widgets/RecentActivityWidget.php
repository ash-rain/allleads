<?php

namespace App\Filament\Widgets;

use App\Models\LeadActivity;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentActivityWidget extends BaseWidget
{
    protected static ?int $sort         = 8;
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('common.recent_activity'))
            ->query(
                LeadActivity::with(['lead', 'user'])
                    ->latest()
                    ->limit(20)
            )
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('common.when'))
                    ->dateTime('d M H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('lead.title')
                    ->label(__('common.lead'))
                    ->searchable()
                    ->url(fn($record) => $record->lead_id
                        ? '/admin/leads/' . $record->lead_id
                        : null),

                Tables\Columns\TextColumn::make('event')
                    ->label(__('common.event'))
                    ->formatStateUsing(fn($state) => __("leads.activity_{$state}", [], 'en')),

                Tables\Columns\TextColumn::make('user.name')
                    ->label(__('common.by'))
                    ->default('System'),
            ])
            ->paginated(false);
    }
}
