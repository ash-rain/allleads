<?php

namespace App\Filament\Resources;

use App\Filament\Clusters\Intelligence\Pages\IntelligenceDashboard;
use App\Filament\Clusters\Intelligence\Pages\WebsiteAnalysisPage;
use App\Filament\Resources\LeadResource\Pages;
use App\Jobs\RunProspectAnalysisJob;
use App\Jobs\RunWebsiteAnalysisJob;
use App\Models\ImportBatch;
use App\Models\Lead;
use App\Models\LeadProspectAnalysis;
use App\Models\LeadWebsiteAnalysis;
use App\Models\Tag;
use App\Models\User;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class LeadResource extends Resource
{
    protected static ?string $model = Lead::class;

    protected static ?string $recordTitleAttribute = 'title';

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-users';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Leads';
    }

    public static function getNavigationLabel(): string
    {
        return __('leads.nav_label');
    }

    public static function getModelLabel(): string
    {
        return __('leads.resource_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('leads.resource_label_plural');
    }

    // ─── Form ─────────────────────────────────────────────────────────────────
    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Schemas\Components\Section::make()->columns(2)->schema([
                Forms\Components\TextInput::make('title')
                    ->label(__('leads.field_title'))
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('category')
                    ->label(__('leads.field_category'))
                    ->maxLength(255),

                Forms\Components\TextInput::make('phone')
                    ->label(__('leads.field_phone'))
                    ->tel()
                    ->maxLength(50),

                Forms\Components\TextInput::make('email')
                    ->label(__('leads.field_email'))
                    ->email()
                    ->maxLength(255),

                Forms\Components\TextInput::make('website')
                    ->label(__('leads.field_website'))
                    ->url()
                    ->maxLength(255),

                Forms\Components\TextInput::make('address')
                    ->label(__('leads.field_address'))
                    ->maxLength(500),

                Forms\Components\TextInput::make('review_rating')
                    ->label(__('leads.field_review_rating'))
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(5)
                    ->step(0.1),

                Forms\Components\Select::make('status')
                    ->label(__('leads.field_status'))
                    ->options([
                        Lead::STATUS_NEW => __('leads.status_new'),
                        Lead::STATUS_CONTACTED => __('leads.status_contacted'),
                        Lead::STATUS_REPLIED => __('leads.status_replied'),
                        Lead::STATUS_CLOSED => __('leads.status_closed'),
                        Lead::STATUS_DISQUALIFIED => __('leads.status_disqualified'),
                    ])
                    ->default(Lead::STATUS_NEW)
                    ->required(),

                Forms\Components\Select::make('assigned_to')
                    ->label(__('leads.field_assignee'))
                    ->relationship('assignee', 'name')
                    ->searchable()
                    ->preload(),
            ]),

            Schemas\Components\Section::make()->schema([
                Forms\Components\Select::make('tags')
                    ->label(__('leads.field_tags'))
                    ->multiple()
                    ->relationship('tags', 'name')
                    ->preload(),
            ]),
        ]);
    }

    // ─── Table ────────────────────────────────────────────────────────────────
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label(__('leads.field_title'))
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),

                Tables\Columns\TextColumn::make('category')
                    ->label(__('leads.field_category'))
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('conversations_count')
                    ->label(__('leads.field_conversations'))
                    ->icon('heroicon-m-chat-bubble-left-ellipsis')
                    ->badge()
                    ->color(fn (Lead $record): string => match (true) {
                        (($record->messages_count ?? 0) + ($record->drafts_count ?? 0)) > 0 => 'success',
                        (bool) $record->email => 'warning',
                        default => 'gray',
                    }
                    )
                    ->getStateUsing(fn (Lead $record): int => ($record->messages_count ?? 0) + ($record->drafts_count ?? 0)
                    )
                    ->url(fn (Lead $record): string => Pages\ViewLead::getUrl(['record' => $record->id]).'?tab=conversation%3A%3Atab')
                    ->tooltip(fn (Lead $record): ?string => $record->email ?: null)
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderByRaw(
                        '(SELECT COUNT(*) FROM email_messages
                              INNER JOIN email_threads ON email_messages.thread_id = email_threads.id
                              WHERE email_threads.lead_id = leads.id) +
                             (SELECT COUNT(*) FROM email_drafts
                              WHERE email_drafts.lead_id = leads.id
                              AND email_drafts.deleted_at IS NULL) '.$direction
                    )
                    )
                    ->toggleable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label(__('leads.field_status'))
                    ->formatStateUsing(fn (string $state) => __("leads.status_{$state}"))
                    ->color(fn (string $state) => match ($state) {
                        Lead::STATUS_NEW => 'primary',
                        Lead::STATUS_CONTACTED => 'info',
                        Lead::STATUS_REPLIED => 'warning',
                        Lead::STATUS_CLOSED => 'success',
                        Lead::STATUS_DISQUALIFIED => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('review_rating')
                    ->label(__('leads.field_review_rating'))
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state >= 4.5 => 'success',
                        $state >= 3.5 => 'warning',
                        $state >= 2.5 => 'gray',
                        default => 'danger',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('website_score')
                    ->label(__('leads.field_website_score'))
                    ->icon(fn (Lead $record): string => ! empty($record->website) ? 'heroicon-o-globe-alt' : 'heroicon-o-x-circle')
                    ->iconColor(fn (Lead $record): string => ! empty($record->website) ? 'success' : 'danger')
                    ->badge()
                    ->getStateUsing(function (Lead $record): string {
                        $score = $record->websiteAnalysis?->result['overall_score'] ?? null;

                        return $score !== null ? (string) $score : "\u{00A0}";
                    })
                    ->formatStateUsing(fn (string $state): string => is_numeric($state) ? $state : '')
                    ->color(function (Lead $record): string {
                        $score = $record->websiteAnalysis?->result['overall_score'] ?? null;

                        return match (true) {
                            $score === null => 'gray',
                            $score >= 70 => 'success',
                            $score >= 40 => 'warning',
                            default => 'danger',
                        };
                    })
                    ->url(fn (Lead $record) => WebsiteAnalysisPage::getUrl(['lead' => $record->id]))
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy(
                            DB::table('lead_website_analyses')
                                ->selectRaw("json_extract(result, '$.overall_score')")
                                ->whereColumn('lead_id', 'leads.id')
                                ->limit(1),
                            $direction
                        );
                    }),

                Tables\Columns\TextColumn::make('prospect_score')
                    ->label(__('leads.field_prospect_score'))
                    ->icon('heroicon-o-cpu-chip')
                    ->badge()
                    ->getStateUsing(function (Lead $record): string {
                        $score = $record->prospectAnalysis?->result['prospect_score'] ?? null;

                        return $score !== null ? (string) $score : "\u{00A0}";
                    })
                    ->formatStateUsing(fn (string $state): string => is_numeric($state) ? $state : '')
                    ->color(function (Lead $record): string {
                        $score = $record->prospectAnalysis?->result['prospect_score'] ?? null;

                        return match (true) {
                            $score === null => 'gray',
                            $score >= 70 => 'success',
                            $score >= 40 => 'warning',
                            default => 'danger',
                        };
                    })
                    ->url(fn (Lead $record) => IntelligenceDashboard::getUrl(['lead' => $record->id]))
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy(
                            DB::table('lead_prospect_analyses')
                                ->selectRaw("json_extract(result, '$.prospect_score')")
                                ->whereColumn('lead_id', 'leads.id')
                                ->limit(1),
                            $direction
                        );
                    }),

                Tables\Columns\TextColumn::make('avg_intelligence_score')
                    ->label(__('leads.field_intelligence_score'))
                    ->icon('heroicon-o-cpu-chip')
                    ->badge()
                    ->getStateUsing(function (Lead $record): string {
                        $scores = [];

                        $prospectScore = $record->prospectAnalysis?->result['prospect_score'] ?? null;
                        if ($prospectScore !== null) {
                            $scores[] = (int) $prospectScore;
                        }

                        $websiteScore = $record->websiteAnalysis?->result['overall_score'] ?? null;
                        if ($websiteScore !== null) {
                            $scores[] = (int) $websiteScore;
                        }

                        return empty($scores) ? "\u{00A0}" : (string) (int) round(array_sum($scores) / count($scores));
                    })
                    ->formatStateUsing(fn (string $state): string => is_numeric($state) ? $state : '')
                    ->color(function (Lead $record): string {
                        $scores = [];

                        $prospectScore = $record->prospectAnalysis?->result['prospect_score'] ?? null;
                        if ($prospectScore !== null) {
                            $scores[] = (int) $prospectScore;
                        }

                        $websiteScore = $record->websiteAnalysis?->result['overall_score'] ?? null;
                        if ($websiteScore !== null) {
                            $scores[] = (int) $websiteScore;
                        }

                        if (empty($scores)) {
                            return 'gray';
                        }

                        $avg = array_sum($scores) / count($scores);

                        return match (true) {
                            $avg >= 70 => 'success',
                            $avg >= 40 => 'warning',
                            default => 'danger',
                        };
                    })
                    ->url(fn (Lead $record) => IntelligenceDashboard::getUrl(['lead' => $record->id]))
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderByRaw("
                            (
                                COALESCE((SELECT json_extract(result, '$.prospect_score') FROM lead_prospect_analyses WHERE lead_id = leads.id LIMIT 1), 0) +
                                COALESCE((SELECT json_extract(result, '$.overall_score') FROM lead_website_analyses WHERE lead_id = leads.id LIMIT 1), 0)
                            ) / NULLIF(
                                (CASE WHEN (SELECT result FROM lead_prospect_analyses WHERE lead_id = leads.id LIMIT 1) IS NOT NULL THEN 1 ELSE 0 END) +
                                (CASE WHEN (SELECT result FROM lead_website_analyses WHERE lead_id = leads.id LIMIT 1) IS NOT NULL THEN 1 ELSE 0 END),
                                0
                            ) {$direction}
                        ");
                    })
                    ->toggleable(),

                Tables\Columns\TextColumn::make('assignee.name')
                    ->label(__('leads.field_assignee'))
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('tags.name')
                    ->label(__('leads.field_tags'))
                    ->badge()
                    ->color(fn ($state, Lead $record) => $record->tags
                        ->firstWhere('name', $state)?->color ?? 'gray')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('leads.field_created_at'))
                    ->since()
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount(['messages', 'drafts']))
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('leads.filter_status'))
                    ->options([
                        Lead::STATUS_NEW => __('leads.status_new'),
                        Lead::STATUS_CONTACTED => __('leads.status_contacted'),
                        Lead::STATUS_REPLIED => __('leads.status_replied'),
                        Lead::STATUS_CLOSED => __('leads.status_closed'),
                        Lead::STATUS_DISQUALIFIED => __('leads.status_disqualified'),
                    ])
                    ->multiple(),

                Tables\Filters\Filter::make('web_dev_prospects')
                    ->label(__('leads.preset_web_dev_prospects'))
                    ->query(fn (Builder $query) => $query->where('review_rating', '>', 4.5)->whereNull('website'))
                    ->toggle(),

                Tables\Filters\Filter::make('no_website')
                    ->label(__('leads.filter_no_website'))
                    ->query(fn (Builder $query) => $query->whereNull('website'))
                    ->toggle(),

                Tables\Filters\Filter::make('has_email')
                    ->label(__('leads.filter_has_email'))
                    ->query(fn (Builder $query) => $query->whereNotNull('email'))
                    ->toggle(),

                Tables\Filters\Filter::make('rating_min')
                    ->label(__('leads.filter_rating_min'))
                    ->form([
                        Forms\Components\TextInput::make('rating_from')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(5)
                            ->step(0.1)
                            ->placeholder('0'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['rating_from'] !== null && $data['rating_from'] !== '',
                            fn ($q) => $q->where('review_rating', '>=', $data['rating_from'])
                        );
                    }),

                Tables\Filters\SelectFilter::make('category')
                    ->label(__('leads.filter_category'))
                    ->options(
                        fn () => Lead::query()
                            ->select('category')
                            ->distinct()
                            ->whereNotNull('category')
                            ->orderBy('category')
                            ->pluck('category', 'category')
                            ->toArray()
                    )
                    ->multiple()
                    ->searchable(),

                Tables\Filters\SelectFilter::make('assigned_to')
                    ->label(__('leads.filter_assignee'))
                    ->relationship('assignee', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('tags')
                    ->label(__('leads.filter_tags'))
                    ->options(Tag::pluck('name', 'id'))
                    ->multiple()
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['values'])) {
                            return $query;
                        }

                        return $query->whereHas('tags', fn ($q) => $q->whereIn('tags.id', $data['values']));
                    }),

                Tables\Filters\SelectFilter::make('import_batch_id')
                    ->label(__('leads.filter_import_batch'))
                    ->options(
                        ImportBatch::query()
                            ->orderByDesc('created_at')
                            ->pluck('filename', 'id')
                            ->toArray()
                    )
                    ->searchable(),

                Tables\Filters\Filter::make('created_at')
                    ->label(__('leads.filter_date_from'))
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label(__('leads.filter_date_from')),
                        Forms\Components\DatePicker::make('until')
                            ->label(__('leads.filter_date_to')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn ($q) => $q->whereDate('created_at', '>=', $data['from']))
                            ->when($data['until'], fn ($q) => $q->whereDate('created_at', '<=', $data['until']));
                    }),

                Tables\Filters\SelectFilter::make('prospect_analysis_status')
                    ->label(__('leads.filter_prospect_analysis'))
                    ->options([
                        'none' => __('leads.analysis_status_none'),
                        LeadProspectAnalysis::STATUS_PENDING => __('leads.analysis_status_pending'),
                        LeadProspectAnalysis::STATUS_COMPLETED => __('leads.analysis_status_completed'),
                        LeadProspectAnalysis::STATUS_FAILED => __('leads.analysis_status_failed'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (blank($data['value'])) {
                            return $query;
                        }

                        if ($data['value'] === 'none') {
                            return $query->whereDoesntHave('prospectAnalysis');
                        }

                        return $query->whereHas(
                            'prospectAnalysis',
                            fn ($q) => $q->where('status', $data['value'])
                        );
                    }),

                Tables\Filters\SelectFilter::make('website_analysis_status')
                    ->label(__('leads.filter_website_analysis'))
                    ->options([
                        'none' => __('leads.analysis_status_none'),
                        LeadWebsiteAnalysis::STATUS_PENDING => __('leads.analysis_status_pending'),
                        LeadWebsiteAnalysis::STATUS_COMPLETED => __('leads.analysis_status_completed'),
                        LeadWebsiteAnalysis::STATUS_FAILED => __('leads.analysis_status_failed'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (blank($data['value'])) {
                            return $query;
                        }

                        if ($data['value'] === 'none') {
                            return $query->whereDoesntHave('websiteAnalysis');
                        }

                        return $query->whereHas(
                            'websiteAnalysis',
                            fn ($q) => $q->where('status', $data['value'])
                        );
                    }),
            ])
            ->persistFiltersInSession()
            ->filtersLayout(Tables\Enums\FiltersLayout::AboveContentCollapsible)
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    // Assign
                    Actions\BulkAction::make('assign')
                        ->label(__('leads.action_assign'))
                        ->icon('heroicon-o-user')
                        ->form([
                            Forms\Components\Select::make('user_id')
                                ->label(__('leads.field_assignee'))
                                ->options(User::pluck('name', 'id'))
                                ->searchable()
                                ->required(),
                        ])
                        ->action(function ($records, array $data): void {
                            $records->each->update(['assigned_to' => $data['user_id']]);
                        })
                        ->deselectRecordsAfterCompletion(),

                    // Change status
                    Actions\BulkAction::make('change_status')
                        ->label(__('leads.action_change_status'))
                        ->icon('heroicon-o-arrow-path')
                        ->form([
                            Forms\Components\Select::make('status')
                                ->label(__('leads.field_status'))
                                ->options([
                                    Lead::STATUS_NEW => __('leads.status_new'),
                                    Lead::STATUS_CONTACTED => __('leads.status_contacted'),
                                    Lead::STATUS_REPLIED => __('leads.status_replied'),
                                    Lead::STATUS_CLOSED => __('leads.status_closed'),
                                    Lead::STATUS_DISQUALIFIED => __('leads.status_disqualified'),
                                ])
                                ->required(),
                        ])
                        ->action(function ($records, array $data): void {
                            $records->each->update(['status' => $data['status']]);
                        })
                        ->deselectRecordsAfterCompletion(),

                    // Add tag
                    Actions\BulkAction::make('add_tag')
                        ->label(__('leads.action_add_tag'))
                        ->icon('heroicon-o-tag')
                        ->form([
                            Forms\Components\Select::make('tag_id')
                                ->label(__('leads.field_tags'))
                                ->options(Tag::pluck('name', 'id'))
                                ->required(),
                        ])
                        ->action(function ($records, array $data): void {
                            $records->each->tags()->syncWithoutDetaching([$data['tag_id']]);
                        })
                        ->deselectRecordsAfterCompletion(),

                    // Remove tag
                    Actions\BulkAction::make('remove_tag')
                        ->label(__('leads.action_remove_tag'))
                        ->icon('heroicon-o-x-mark')
                        ->color('warning')
                        ->form([
                            Forms\Components\Select::make('tag_id')
                                ->label(__('leads.field_tags'))
                                ->options(Tag::pluck('name', 'id'))
                                ->required(),
                        ])
                        ->action(function ($records, array $data): void {
                            $records->each->tags()->detach($data['tag_id']);
                        })
                        ->deselectRecordsAfterCompletion(),

                    // Analyse leads (AI prospect intelligence)
                    Actions\BulkAction::make('analyse_leads')
                        ->label(__('leads.action_analyse_leads'))
                        ->icon('heroicon-o-cpu-chip')
                        ->color('info')
                        ->requiresConfirmation()
                        ->modalHeading(__('leads.analysis_confirm_heading'))
                        ->modalDescription(__('leads.analysis_bulk_confirm_body'))
                        ->action(function ($records): void {
                            $dispatched = 0;

                            foreach ($records as $lead) {
                                if ($lead->prospectAnalysis?->status === LeadProspectAnalysis::STATUS_PENDING) {
                                    continue;
                                }

                                RunProspectAnalysisJob::dispatch($lead, auth()->id());
                                $dispatched++;
                            }

                            Notification::make()
                                ->title(trans_choice('leads.analysis_queued_plural', $dispatched, ['count' => $dispatched]))
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    // Analyse websites (AI website intelligence)
                    Actions\BulkAction::make('analyse_websites')
                        ->label(__('leads.action_analyse_websites'))
                        ->icon('heroicon-o-globe-alt')
                        ->color('info')
                        ->requiresConfirmation()
                        ->modalDescription(__('leads.website_analysis_bulk_confirm'))
                        ->action(function ($records): void {
                            $dispatched = 0;

                            foreach ($records as $lead) {
                                if (! $lead->website) {
                                    continue;
                                }

                                if ($lead->websiteAnalysis?->status === LeadWebsiteAnalysis::STATUS_PENDING) {
                                    continue;
                                }

                                RunWebsiteAnalysisJob::dispatch($lead, auth()->id());
                                $dispatched++;
                            }

                            Notification::make()
                                ->title(trans_choice('leads.website_analysis_bulk_queued', $dispatched, ['count' => $dispatched]))
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    // ─── Pages ────────────────────────────────────────────────────────────────
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLeads::route('/'),
            'create' => Pages\CreateLead::route('/create'),
            'view' => Pages\ViewLead::route('/{record}'),
            'edit' => Pages\EditLead::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['tags', 'assignee', 'prospectAnalysis', 'websiteAnalysis']);
    }
}
