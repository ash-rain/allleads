<?php

namespace App\Filament\Pages;

use App\Models\AiSetting;
use App\Models\BusinessSetting;
use App\Services\Ai\AiProviderFactory;
use App\Services\Intelligence\WebsiteScraper;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BusinessSettings extends Page
{
    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-building-office';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Settings';
    }

    protected static ?int $navigationSort = 5;

    protected static ?string $title = 'Business Settings';

    protected string $view = 'filament.pages.business-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $setting = BusinessSetting::singleton();
        $this->form->fill($setting->toArray());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make(__('business.section_identity'))
                    ->description(__('business.section_identity_description'))
                    ->schema([
                        TextInput::make('business_name')
                            ->label(__('business.business_name'))
                            ->maxLength(255)
                            ->required(),

                        TextInput::make('website_url')
                            ->label(__('business.website_url'))
                            ->url()
                            ->maxLength(255)
                            ->placeholder('https://yourcompany.com'),

                        TextInput::make('industry')
                            ->label(__('business.industry'))
                            ->maxLength(255),

                        Select::make('company_size')
                            ->label(__('business.company_size'))
                            ->options([
                                '1-10' => __('business.size_1_10'),
                                '11-50' => __('business.size_11_50'),
                                '51-200' => __('business.size_51_200'),
                                '201+' => __('business.size_201_plus'),
                            ]),

                        TextInput::make('year_founded')
                            ->label(__('business.year_founded'))
                            ->maxLength(4)
                            ->placeholder('2020'),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make(__('business.section_what_we_do'))
                    ->description(__('business.section_what_we_do_description'))
                    ->schema([
                        Textarea::make('business_description')
                            ->label(__('business.business_description'))
                            ->helperText(__('business.business_description_help'))
                            ->rows(3)
                            ->columnSpanFull()
                            ->required(),

                        Textarea::make('key_services')
                            ->label(__('business.key_services'))
                            ->helperText(__('business.key_services_help'))
                            ->rows(4)
                            ->columnSpanFull(),

                        Textarea::make('unique_selling_points')
                            ->label(__('business.unique_selling_points'))
                            ->helperText(__('business.unique_selling_points_help'))
                            ->rows(2)
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),

                Section::make(__('business.section_target_market'))
                    ->description(__('business.section_target_market_description'))
                    ->schema([
                        Textarea::make('target_audience')
                            ->label(__('business.target_audience'))
                            ->helperText(__('business.target_audience_help'))
                            ->rows(2)
                            ->columnSpanFull(),

                        TextInput::make('geographic_focus')
                            ->label(__('business.geographic_focus'))
                            ->helperText(__('business.geographic_focus_help'))
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),

                Section::make(__('business.section_sales_context'))
                    ->description(__('business.section_sales_context_description'))
                    ->schema([
                        Textarea::make('value_proposition')
                            ->label(__('business.value_proposition'))
                            ->helperText(__('business.value_proposition_help'))
                            ->rows(2)
                            ->columnSpanFull(),

                        Textarea::make('common_pain_points')
                            ->label(__('business.common_pain_points'))
                            ->helperText(__('business.common_pain_points_help'))
                            ->rows(3)
                            ->columnSpanFull(),

                        TextInput::make('call_to_action')
                            ->label(__('business.call_to_action'))
                            ->helperText(__('business.call_to_action_help'))
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Textarea::make('social_proof')
                            ->label(__('business.social_proof'))
                            ->helperText(__('business.social_proof_help'))
                            ->rows(2)
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
            ])
            ->statePath('data')
            ->columns(2);
    }

    public function save(): void
    {
        $setting = BusinessSetting::singleton();
        $setting->update($this->form->getState());

        Notification::make()
            ->title(__('common.saved'))
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label(__('common.save'))
                ->action('save'),

            Action::make('generate_from_website')
                ->label(__('business.generate_from_website'))
                ->icon('heroicon-o-sparkles')
                ->color('gray')
                ->modalDescription(__('business.generate_from_website_description'))
                ->schema([
                    TextInput::make('url')
                        ->label(__('business.generate_from_website_url_label'))
                        ->url()
                        ->required()
                        ->placeholder(__('business.generate_from_website_url_placeholder')),
                ])
                ->action(function (array $data): void {
                    $this->generateFromWebsite($data['url']);
                }),
        ];
    }

    private function generateFromWebsite(string $url): void
    {
        try {
            $scraper = app(WebsiteScraper::class);
            $scrapedData = $scraper->scrape($url);

            $aiSetting = AiSetting::singleton();
            $provider = AiProviderFactory::makeWithFallback($aiSetting);

            $system = $this->buildGenerateSystemPrompt();
            $user = $this->buildGenerateUserPrompt($url, $scrapedData);

            $raw = $provider->complete($system, $user, [
                'model' => $aiSetting->model,
                'temperature' => 0.3,
                'max_tokens' => 2000,
                'timeout' => (int) $aiSetting->timeout,
            ]);

            $parsed = $this->parseGenerateResponse($raw);

            $this->form->fill(array_merge($this->data, $parsed, ['website_url' => $url]));

            Notification::make()
                ->title(__('business.generated_success'))
                ->success()
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title(__('business.generated_error'))
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    private function buildGenerateSystemPrompt(): string
    {
        return <<<'PROMPT'
You are a business analyst. Analyse the scraped website data and extract a structured business profile as JSON.

Return ONLY valid JSON with these exact keys (all string values, null if not determinable):
- business_name
- industry
- company_size (one of: "1-10", "11-50", "51-200", "201+", or null)
- year_founded
- business_description (2-3 sentences about what the company does)
- key_services (comma-separated list of main services or products)
- unique_selling_points (what makes them different)
- target_audience (who their customers are)
- geographic_focus (region or city, null if global)
- value_proposition (their core offer in 1-2 sentences)
- common_pain_points (problems they help their customers solve)
- call_to_action (their typical CTA like "Book a demo" or "Get a free quote")
- social_proof (testimonials, client names, or stats if visible)

Return ONLY valid JSON with those 13 keys, no extra text or markdown.
PROMPT;
    }

    /**
     * @param  array<string, mixed>  $scrapedData
     */
    private function buildGenerateUserPrompt(string $url, array $scrapedData): string
    {
        $lines = ["Website URL: {$url}"];

        if (! empty($scrapedData['company_name'])) {
            $lines[] = "Detected company name: {$scrapedData['company_name']}";
        }

        if (! empty($scrapedData['tech_stack'])) {
            $lines[] = 'Tech stack: '.implode(', ', $scrapedData['tech_stack']);
        }

        if (! empty($scrapedData['pricing_tiers'])) {
            $lines[] = 'Pricing tiers: '.implode(', ', array_column($scrapedData['pricing_tiers'], 'name'));
        }

        if (! empty($scrapedData['job_postings'])) {
            $lines[] = 'Open positions: '.implode(', ', array_slice($scrapedData['job_postings'], 0, 5));
        }

        if (! empty($scrapedData['team_members'])) {
            $names = array_slice(array_column($scrapedData['team_members'], 'name'), 0, 5);
            $lines[] = 'Team members: '.implode(', ', $names);
        }

        if (! empty($scrapedData['contact_info'])) {
            foreach ($scrapedData['contact_info'] as $type => $value) {
                $lines[] = ucfirst($type).': '.$value;
            }
        }

        if (! empty($scrapedData['social_links'])) {
            foreach ($scrapedData['social_links'] as $platform => $url) {
                $lines[] = ucfirst($platform).': '.$url;
            }
        }

        if (! empty($scrapedData['page_text'])) {
            $lines[] = 'Homepage text: '.$scrapedData['page_text'];
        }

        return 'Extract the business profile from this website data:'."\n".implode("\n", $lines);
    }

    /**
     * @return array<string, mixed>
     */
    private function parseGenerateResponse(string $raw): array
    {
        $cleaned = preg_replace('/^```(?:json)?\s*/m', '', $raw);
        $cleaned = preg_replace('/\s*```$/m', '', $cleaned ?? $raw);

        $decoded = json_decode(trim($cleaned ?? ''), true);

        if (! is_array($decoded)) {
            throw new \RuntimeException('AI returned invalid JSON: '.mb_substr($raw, 0, 200));
        }

        // Only keep known safe keys — do not blindly merge unknown keys into form state
        $allowed = [
            'business_name',
            'industry',
            'company_size',
            'year_founded',
            'business_description',
            'key_services',
            'unique_selling_points',
            'target_audience',
            'geographic_focus',
            'value_proposition',
            'common_pain_points',
            'call_to_action',
            'social_proof',
        ];

        return array_filter(
            array_intersect_key($decoded, array_flip($allowed)),
            fn ($v) => $v !== null && $v !== ''
        );
    }
}
