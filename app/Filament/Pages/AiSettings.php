<?php

namespace App\Filament\Pages;

use App\Models\AiSetting;
use App\Services\Ai\AiProviderFactory;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Slider;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class AiSettings extends Page
{
    protected static ?string $navigationIcon    = 'heroicon-o-cpu-chip';
    protected static ?string $navigationGroup   = 'Settings';
    protected static ?int    $navigationSort    = 10;
    protected static ?string $title             = 'AI Settings';
    protected static string  $view              = 'filament.pages.ai-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $setting = AiSetting::singleton();
        $this->form->fill($setting->toArray());
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Providers')
                    ->tabs([
                        Tab::make('OpenRouter')
                            ->schema([
                                Select::make('openrouter_default_model')
                                    ->label(__('ai.model'))
                                    ->options(fn() => $this->loadModels('openrouter'))
                                    ->searchable()
                                    ->helperText(__('ai.free_models_only')),
                            ]),
                        Tab::make('Groq')
                            ->schema([
                                Select::make('groq_default_model')
                                    ->label(__('ai.model'))
                                    ->options(fn() => $this->loadModels('groq'))
                                    ->searchable(),
                            ]),
                        Tab::make('Gemini')
                            ->schema([
                                Select::make('gemini_default_model')
                                    ->label(__('ai.model'))
                                    ->options(fn() => $this->loadModels('gemini'))
                                    ->searchable(),
                            ]),
                    ])
                    ->columnSpanFull(),

                Select::make('provider')
                    ->label(__('ai.active_provider'))
                    ->options([
                        'openrouter' => 'OpenRouter (free models)',
                        'groq'       => 'Groq',
                        'gemini'     => 'Google Gemini',
                    ])
                    ->required(),

                Select::make('model')
                    ->label(__('ai.model'))
                    ->options(fn($get) => $this->loadModels($get('provider') ?? 'openrouter'))
                    ->searchable()
                    ->required(),

                Select::make('language')
                    ->label(__('ai.language'))
                    ->options([
                        'English'    => 'English',
                        'Bulgarian'  => 'Bulgarian',
                        'German'     => 'German',
                        'French'     => 'French',
                        'Spanish'    => 'Spanish',
                    ])
                    ->required(),

                Select::make('tone')
                    ->label(__('ai.tone'))
                    ->options([
                        'professional' => __('ai.tone_professional'),
                        'friendly'     => __('ai.tone_friendly'),
                        'casual'       => __('ai.tone_casual'),
                        'formal'       => __('ai.tone_formal'),
                    ])
                    ->required(),

                Select::make('length')
                    ->label(__('ai.length'))
                    ->options([
                        'short'  => __('ai.length_short'),
                        'medium' => __('ai.length_medium'),
                        'long'   => __('ai.length_long'),
                    ])
                    ->required(),

                Select::make('personalisation')
                    ->label(__('ai.personalisation'))
                    ->options([
                        'low'    => __('ai.personalisation_low'),
                        'medium' => __('ai.personalisation_medium'),
                        'high'   => __('ai.personalisation_high'),
                    ])
                    ->required(),

                Select::make('opener_style')
                    ->label(__('ai.opener_style'))
                    ->options([
                        'question'    => __('ai.opener_question'),
                        'compliment'  => __('ai.opener_compliment'),
                        'observation' => __('ai.opener_observation'),
                        'direct'      => __('ai.opener_direct'),
                    ])
                    ->required(),

                TextInput::make('temperature')
                    ->label(__('ai.temperature'))
                    ->numeric()
                    ->step(0.1)
                    ->minValue(0)
                    ->maxValue(2)
                    ->default(0.7),

                TextInput::make('max_tokens')
                    ->label(__('ai.max_tokens'))
                    ->numeric()
                    ->step(100)
                    ->minValue(100)
                    ->maxValue(4000)
                    ->default(800),

                Toggle::make('include_portfolio')
                    ->label(__('ai.include_portfolio')),

                Toggle::make('include_audit')
                    ->label(__('ai.include_audit')),

                Toggle::make('include_cta')
                    ->label(__('ai.include_cta')),

                Toggle::make('include_ps')
                    ->label(__('ai.include_ps')),

                Textarea::make('custom_system_prompt')
                    ->label(__('ai.custom_system_prompt'))
                    ->rows(6)
                    ->columnSpanFull()
                    ->helperText(__('ai.custom_prompt_help')),
            ])
            ->statePath('data')
            ->columns(2);
    }

    public function save(): void
    {
        $setting = AiSetting::singleton();
        $setting->update($this->form->getState());

        Notification::make()
            ->title(__('common.saved'))
            ->success()
            ->send();
    }

    public function refreshModels(string $provider): void
    {
        try {
            $models = AiProviderFactory::makeFromName($provider)->availableModels();

            Notification::make()
                ->title(__('ai.models_refreshed', ['count' => count($models)]))
                ->success()
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title(__('ai.models_refresh_failed'))
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label(__('common.save'))
                ->action('save'),

            Action::make('refresh_openrouter')
                ->label('Refresh OpenRouter Models')
                ->color('gray')
                ->action(fn() => $this->refreshModels('openrouter')),

            Action::make('refresh_groq')
                ->label('Refresh Groq Models')
                ->color('gray')
                ->action(fn() => $this->refreshModels('groq')),
        ];
    }

    /** Load available models for a provider, falling back to config. */
    private function loadModels(string $provider): array
    {
        try {
            $models = AiProviderFactory::makeFromName($provider)->availableModels();
            return array_combine($models, $models);
        } catch (\Throwable) {
            $configured = config("ai.{$provider}.models", []);
            return array_combine($configured, $configured);
        }
    }
}
