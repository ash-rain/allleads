<?php

namespace App\Filament\Pages\Auth;

use App\Models\AiSetting;
use App\Models\Business;
use App\Services\Ai\AiProviderFactory;
use App\Services\Intelligence\WebsiteScraper;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Tenancy\RegisterTenant;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RegisterBusiness extends RegisterTenant
{
    public static function getLabel(): string
    {
        return __('business.register_title');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make(__('business.register_from_url_section'))
                    ->description(__('business.register_from_url_description'))
                    ->schema([
                        TextInput::make('website_url')
                            ->label(__('business.website_url'))
                            ->url()
                            ->maxLength(255)
                            ->placeholder('https://yourcompany.com')
                            ->live(onBlur: true)
                            ->columnSpanFull(),

                        Actions::make([
                            Action::make('generate_from_url')
                                ->label(__('business.generate_from_website'))
                                ->icon('heroicon-o-sparkles')
                                ->color('primary')
                                ->action(function ($get, $set): void {
                                    $url = $get('website_url');

                                    if (empty($url)) {
                                        Notification::make()
                                            ->title(__('business.register_url_required'))
                                            ->warning()
                                            ->send();

                                        return;
                                    }

                                    $this->generateFromUrl($url, $set);
                                }),
                        ]),
                    ])
                    ->columnSpanFull(),

                Section::make(__('business.section_identity'))
                    ->schema([
                        TextInput::make('name')
                            ->label(__('business.business_name'))
                            ->maxLength(255)
                            ->required(),

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

                        TextInput::make('tag_color')
                            ->label(__('business.tag_color'))
                            ->type('color')
                            ->default('#3b82f6'),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make(__('business.section_what_we_do'))
                    ->schema([
                        Textarea::make('description')
                            ->label(__('business.business_description'))
                            ->rows(3)
                            ->columnSpanFull(),

                        Textarea::make('key_services')
                            ->label(__('business.key_services'))
                            ->rows(3)
                            ->columnSpanFull(),

                        Textarea::make('unique_selling_points')
                            ->label(__('business.unique_selling_points'))
                            ->rows(2)
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull()
                    ->collapsible(),

                Section::make(__('business.section_target_market'))
                    ->schema([
                        Textarea::make('target_audience')
                            ->label(__('business.target_audience'))
                            ->rows(2)
                            ->columnSpanFull(),

                        TextInput::make('geographic_focus')
                            ->label(__('business.geographic_focus'))
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull()
                    ->collapsible()
                    ->collapsed(),

                Section::make(__('business.section_sales_context'))
                    ->schema([
                        Textarea::make('value_proposition')
                            ->label(__('business.value_proposition'))
                            ->rows(2)
                            ->columnSpanFull(),

                        Textarea::make('common_pain_points')
                            ->label(__('business.common_pain_points'))
                            ->rows(2)
                            ->columnSpanFull(),

                        TextInput::make('call_to_action')
                            ->label(__('business.call_to_action'))
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull()
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    protected function handleRegistration(array $data): Business
    {
        $business = Business::create($data);

        $business->users()->attach(auth()->user(), ['role' => 'owner']);

        return $business;
    }

    private function generateFromUrl(string $url, callable $set): void
    {
        try {
            $scraper = app(WebsiteScraper::class);
            $scrapedData = $scraper->scrape($url);

            $parsed = $this->extractWithAi($url, $scrapedData);

            // Fill all extracted fields into the form
            foreach ($parsed as $field => $value) {
                $set($field, $value);
            }

            $set('website_url', $url);

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

    /**
     * @param  array<string, mixed>  $scrapedData
     * @return array<string, mixed>
     */
    private function extractWithAi(string $url, array $scrapedData): array
    {
        // Try to use the first available business's AI settings, or fall back to defaults
        $user = auth()->user();
        $existingBusiness = $user->businesses()->first();
        $aiSetting = $existingBusiness
            ? $existingBusiness->aiSettingOrCreate()
            : AiSetting::singleton();

        $provider = AiProviderFactory::makeWithFallback($aiSetting);

        $system = $this->buildSystemPrompt();
        $userPrompt = $this->buildUserPrompt($url, $scrapedData);

        $raw = $provider->complete($system, $userPrompt, [
            'model' => $aiSetting->model,
            'temperature' => 0.3,
            'max_tokens' => 2000,
            'timeout' => (int) $aiSetting->timeout,
        ]);

        return $this->parseResponse($raw);
    }

    private function buildSystemPrompt(): string
    {
        return <<<'PROMPT'
You are a business analyst. Analyse the scraped website data and extract a structured business profile as JSON.

Return ONLY valid JSON with these exact keys (all string values, null if not determinable):
- name
- industry
- company_size (one of: "1-10", "11-50", "51-200", "201+", or null)
- year_founded
- description (2-3 sentences about what the company does)
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
    private function buildUserPrompt(string $url, array $scrapedData): string
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
            foreach ($scrapedData['social_links'] as $platform => $link) {
                $lines[] = ucfirst($platform).': '.$link;
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
    private function parseResponse(string $raw): array
    {
        $cleaned = preg_replace('/^```(?:json)?\s*/m', '', $raw);
        $cleaned = preg_replace('/\s*```$/m', '', $cleaned ?? $raw);

        // Strip <think>...</think> blocks from reasoning models
        $cleaned = preg_replace('/<think>[\s\S]*?<\/think>/i', '', $cleaned ?? $raw);

        $decoded = json_decode(trim($cleaned ?? ''), true);

        if (! is_array($decoded)) {
            throw new \RuntimeException('AI returned invalid JSON: '.mb_substr($raw, 0, 200));
        }

        $allowed = [
            'name',
            'industry',
            'company_size',
            'year_founded',
            'description',
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
