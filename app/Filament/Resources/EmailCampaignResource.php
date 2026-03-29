<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmailCampaignResource\Pages;
use App\Models\EmailCampaign;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class EmailCampaignResource extends Resource
{
    protected static ?string $model = EmailCampaign::class;

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-megaphone';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Email & Campaigns';
    }

    public static function getModelLabel(): string
    {
        return __('emails.campaign_resource_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('emails.campaign_resource_label_plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('emails.campaign_nav_label');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\Section::make()->columns(2)->schema([
                Forms\Components\TextInput::make('name')
                    ->label(__('emails.campaign_field_name'))
                    ->required()
                    ->maxLength(255),

                Forms\Components\Select::make('status')
                    ->label(__('emails.campaign_field_status'))
                    ->options([
                        'draft' => __('emails.campaign_status_draft'),
                        'running' => __('emails.campaign_status_running'),
                        'paused' => __('emails.campaign_status_paused'),
                        'completed' => __('emails.campaign_status_completed'),
                    ])
                    ->default('draft')
                    ->required(),

                Forms\Components\Select::make('provider')
                    ->label(__('emails.campaign_field_provider'))
                    ->options([
                        'openrouter' => __('ai.provider_openrouter'),
                        'groq' => __('ai.provider_groq'),
                        'gemini' => __('ai.provider_gemini'),
                    ])
                    ->reactive(),

                Forms\Components\TextInput::make('model')
                    ->label(__('emails.campaign_field_model'))
                    ->maxLength(255),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('emails.campaign_field_name'))
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),

                Tables\Columns\BadgeColumn::make('status')
                    ->label(__('emails.campaign_field_status'))
                    ->formatStateUsing(fn ($state) => __("emails.campaign_status_{$state}"))
                    ->color(fn (string $state) => match ($state) {
                        'draft' => 'gray',
                        'running' => 'info',
                        'paused' => 'warning',
                        'completed' => 'success',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('provider')
                    ->label(__('emails.campaign_field_provider'))
                    ->formatStateUsing(fn ($state) => __("ai.provider_{$state}")),

                Tables\Columns\TextColumn::make('model')
                    ->label(__('emails.campaign_field_model'))
                    ->toggleable(),

                Tables\Columns\TextColumn::make('lead_count')
                    ->label(__('emails.campaign_field_lead_count'))
                    ->numeric()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('creator.name')
                    ->label(__('emails.campaign_field_created_by'))
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('common.created_at'))
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmailCampaigns::route('/'),
            'create' => Pages\CreateEmailCampaign::route('/create'),
            'edit' => Pages\EditEmailCampaign::route('/{record}/edit'),
        ];
    }
}
