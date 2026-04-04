<?php

namespace App\Filament\Pages;

use App\Models\AiSetting;
use App\Services\Ai\AiProviderFactory;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Http;

class AiSettings extends Page
{
    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-cpu-chip';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Settings';
    }

    protected static ?int $navigationSort = 10;

    protected static ?string $title = 'AI Settings';

    protected string $view = 'filament.pages.ai-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $setting = AiSetting::singleton();
        $this->form->fill($setting->toArray());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make(__('ai.api_keys_section'))
                    ->description(__('ai.api_keys_description'))
                    ->schema([
                        TextInput::make('openrouter_api_key')
                            ->label(__('ai.openrouter_api_key'))
                            ->password()
                            ->revealable()
                            ->helperText(__('ai.openrouter_api_key_help'))
                            ->hint(fn () => $this->providerStatus('openrouter'))
                            ->hintColor(fn () => $this->providerStatusColor('openrouter'))
                            ->hintAction(
                                Action::make('test_openrouter')
                                    ->label(__('ai.test_connection'))
                                    ->action(fn () => $this->testProvider('openrouter'))
                            )
                            ->columnSpanFull(),

                        TextInput::make('groq_api_key')
                            ->label(__('ai.groq_api_key'))
                            ->password()
                            ->revealable()
                            ->helperText(__('ai.groq_api_key_help'))
                            ->hint(fn () => $this->providerStatus('groq'))
                            ->hintColor(fn () => $this->providerStatusColor('groq'))
                            ->hintAction(
                                Action::make('test_groq')
                                    ->label(__('ai.test_connection'))
                                    ->action(fn () => $this->testProvider('groq'))
                            )
                            ->columnSpanFull(),

                        TextInput::make('gemini_api_key')
                            ->label(__('ai.gemini_api_key'))
                            ->password()
                            ->revealable()
                            ->helperText(__('ai.gemini_api_key_help'))
                            ->hint(fn () => $this->providerStatus('gemini'))
                            ->hintColor(fn () => $this->providerStatusColor('gemini'))
                            ->hintAction(
                                Action::make('test_gemini')
                                    ->label(__('ai.test_connection'))
                                    ->action(fn () => $this->testProvider('gemini'))
                            )
                            ->columnSpanFull(),
                    ])
                    ->columns(1)
                    ->columnSpanFull(),

                Section::make('General')
                    ->description('AI provider, model, language, and generation parameters.')
                    ->schema([
                        Select::make('provider')
                            ->label(__('ai.active_provider'))
                            ->options([
                                'openrouter' => 'OpenRouter (free models)',
                                'groq' => 'Groq',
                                'gemini' => 'Google Gemini',
                            ])
                            ->live()
                            ->afterStateUpdated(fn (Set $set) => $set('model', null))
                            ->required(),

                        Select::make('model')
                            ->label(__('ai.model'))
                            ->options(fn ($get) => $this->loadModels($get('provider') ?? 'openrouter'))
                            ->searchable()
                            ->required(),

                        Select::make('language')
                            ->label(__('ai.language'))
                            ->options([
                                'English' => 'English',
                                'Bulgarian' => 'Bulgarian',
                                'German' => 'German',
                                'French' => 'French',
                                'Spanish' => 'Spanish',
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
                            ->maxValue(9000)
                            ->default(3000),

                        TextInput::make('timeout')
                            ->label(__('ai.timeout'))
                            ->numeric()
                            ->step(5)
                            ->minValue(10)
                            ->maxValue(300)
                            ->suffix('s')
                            ->default(90),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make('Email Generation')
                    ->description('Defaults applied when generating cold emails.')
                    ->schema([
                        Select::make('tone')
                            ->label(__('ai.tone'))
                            ->options([
                                'professional' => __('ai.tone_professional'),
                                'friendly' => __('ai.tone_friendly'),
                                'casual' => __('ai.tone_casual'),
                                'formal' => __('ai.tone_formal'),
                            ])
                            ->required(),

                        Select::make('length')
                            ->label(__('ai.length'))
                            ->options([
                                'short' => __('ai.length_short'),
                                'medium' => __('ai.length_medium'),
                                'long' => __('ai.length_long'),
                            ])
                            ->required(),

                        Select::make('personalisation')
                            ->label(__('ai.personalisation'))
                            ->options([
                                'low' => __('ai.personalisation_low'),
                                'medium' => __('ai.personalisation_medium'),
                                'high' => __('ai.personalisation_high'),
                            ])
                            ->required(),

                        Select::make('opener_style')
                            ->label(__('ai.opener_style'))
                            ->options([
                                'question' => __('ai.opener_question'),
                                'compliment' => __('ai.opener_compliment'),
                                'observation' => __('ai.opener_observation'),
                                'direct' => __('ai.opener_direct'),
                            ])
                            ->required(),

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
                    ->columns(2)
                    ->columnSpanFull(),
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

            ActionGroup::make([
                Action::make('refresh_openrouter')
                    ->label(__('ai.refresh_openrouter_models'))
                    ->action(fn () => $this->refreshModels('openrouter')),

                Action::make('refresh_groq')
                    ->label(__('ai.refresh_groq_models'))
                    ->action(fn () => $this->refreshModels('groq')),

                Action::make('refresh_gemini')
                    ->label(__('ai.refresh_gemini_models'))
                    ->action(fn () => $this->refreshModels('gemini')),
            ])
                ->label(__('ai.refresh_models'))
                ->color('gray'),
        ];
    }

    public function testProvider(string $provider): void
    {
        $key = $this->data["{$provider}_api_key"] ?? null;

        if (empty($key)) {
            $key = (string) config("ai.{$provider}.api_key", '');
        }

        if (empty($key)) {
            Notification::make()
                ->title(__('ai.test_provider_no_key'))
                ->warning()
                ->send();

            return;
        }

        try {
            match ($provider) {
                'openrouter' => $this->pingOpenAiCompatible(
                    (string) config('ai.openrouter.endpoint', 'https://openrouter.ai/api/v1'),
                    $key
                ),
                'groq' => $this->pingOpenAiCompatible(
                    (string) config('ai.groq.endpoint', 'https://api.groq.com/openai/v1'),
                    $key
                ),
                'gemini' => $this->pingGemini($key),
            };

            Notification::make()
                ->title(__('ai.test_provider_success'))
                ->success()
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title(__('ai.test_provider_failed'))
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    private function providerStatus(string $provider): string
    {
        $setting = AiSetting::singleton();

        if (filled($setting->apiKeyFor($provider))) {
            return __('ai.api_key_status_active');
        }

        if (filled(config("ai.{$provider}.api_key"))) {
            return __('ai.api_key_status_env');
        }

        return __('ai.api_key_status_not_configured');
    }

    private function providerStatusColor(string $provider): string
    {
        $setting = AiSetting::singleton();

        if (filled($setting->apiKeyFor($provider))) {
            return 'success';
        }

        if (filled(config("ai.{$provider}.api_key"))) {
            return 'info';
        }

        return 'danger';
    }

    private function pingOpenAiCompatible(string $endpoint, string $key): void
    {
        $response = Http::withToken($key)->timeout(10)->get("{$endpoint}/models");

        if (! $response->successful()) {
            throw new \RuntimeException(
                sprintf('HTTP %d: %s', $response->status(), mb_substr($response->body(), 0, 200))
            );
        }
    }

    private function pingGemini(string $key): void
    {
        $endpoint = config('ai.gemini.endpoint', 'https://generativelanguage.googleapis.com/v1beta');
        $response = Http::timeout(10)->get("{$endpoint}/models", ['key' => $key]);

        if (! $response->successful()) {
            throw new \RuntimeException(
                sprintf('HTTP %d: %s', $response->status(), mb_substr($response->body(), 0, 200))
            );
        }
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
