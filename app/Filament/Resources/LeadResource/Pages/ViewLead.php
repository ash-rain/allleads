<?php

namespace App\Filament\Resources\LeadResource\Pages;

use App\Filament\Resources\LeadResource;
use App\Jobs\RunProspectAnalysisJob;
use App\Livewire\ConversationView;
use App\Livewire\LeadActivity as LeadActivityFeed;
use App\Livewire\LeadNotes;
use App\Livewire\ProspectAnalysis;
use App\Models\Lead;
use App\Models\LeadProspectAnalysis;
use Filament\Actions;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Livewire as LivewireEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class ViewLead extends ViewRecord
{
    protected static string $resource = LeadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('analyse_lead')
                ->label(__('leads.action_analyse_lead'))
                ->icon('heroicon-o-cpu-chip')
                ->color('info')
                ->requiresConfirmation()
                ->modalHeading(__('leads.analysis_confirm_heading'))
                ->modalDescription(__('leads.analysis_confirm_body'))
                ->action(function (): void {
                    /** @var Lead $record */
                    $record = $this->getRecord();
                    LeadProspectAnalysis::updateOrCreate(
                        ['lead_id' => $record->id],
                        [
                            'status' => LeadProspectAnalysis::STATUS_PENDING,
                            'result' => null,
                            'error_message' => null,
                            'started_at' => now(),
                            'completed_at' => null,
                        ],
                    );
                    RunProspectAnalysisJob::dispatch($record, auth()->id());
                    Notification::make()
                        ->title(__('leads.analysis_queued'))
                        ->success()
                        ->send();
                }),
            Actions\EditAction::make(),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->record($this->getRecord())
            ->schema([
                Tabs::make('tabs')->tabs([

                    Tab::make(__('leads.tab_overview'))
                        ->schema([
                            Section::make()->columns(2)->schema([
                                TextEntry::make('title')
                                    ->label(__('leads.field_title'))
                                    ->weight('bold'),

                                TextEntry::make('category')
                                    ->label(__('leads.field_category')),

                                TextEntry::make('address')
                                    ->label(__('leads.field_address'))
                                    ->columnSpanFull(),

                                TextEntry::make('phone')
                                    ->label(__('leads.field_phone'))
                                    ->copyable(),

                                TextEntry::make('email')
                                    ->label(__('leads.field_email'))
                                    ->copyable(),

                                TextEntry::make('website')
                                    ->label(__('leads.field_website'))
                                    ->url(fn (Lead $record) => $record->website)
                                    ->openUrlInNewTab(),

                                TextEntry::make('review_rating')
                                    ->label(__('leads.field_review_rating'))
                                    ->badge()
                                    ->color(fn ($state) => match (true) {
                                        $state >= 4.5 => 'success',
                                        $state >= 3.5 => 'warning',
                                        default => 'danger',
                                    }),

                                TextEntry::make('status')
                                    ->label(__('leads.field_status'))
                                    ->badge()
                                    ->formatStateUsing(fn ($state) => __("leads.status_{$state}"))
                                    ->color(fn (string $state) => match ($state) {
                                        Lead::STATUS_NEW => 'primary',
                                        Lead::STATUS_CONTACTED => 'info',
                                        Lead::STATUS_REPLIED => 'warning',
                                        Lead::STATUS_CLOSED => 'success',
                                        Lead::STATUS_DISQUALIFIED => 'danger',
                                        default => 'gray',
                                    }),

                                TextEntry::make('assignee.name')
                                    ->label(__('leads.field_assignee')),

                                TextEntry::make('tags.name')
                                    ->label(__('leads.field_tags'))
                                    ->badge()
                                    ->separator(','),
                            ]),
                        ]),

                    Tab::make(__('leads.tab_conversation'))
                        ->schema([
                            LivewireEntry::make(ConversationView::class)
                                ->data(fn (Lead $record) => ['leadId' => $record->id]),
                        ]),

                    Tab::make(__('leads.tab_notes'))
                        ->schema([
                            LivewireEntry::make(LeadNotes::class)
                                ->data(fn (Lead $record) => ['leadId' => $record->id]),
                        ]),

                    Tab::make(__('leads.tab_activity'))
                        ->schema([
                            LivewireEntry::make(LeadActivityFeed::class)
                                ->data(fn (Lead $record) => ['leadId' => $record->id]),
                        ]),

                    Tab::make(__('leads.tab_intelligence'))
                        ->schema([
                            LivewireEntry::make(ProspectAnalysis::class)
                                ->data(fn (Lead $record) => ['leadId' => $record->id]),
                        ]),

                ])->columnSpanFull(),
            ]);
    }
}
