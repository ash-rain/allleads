<?php

namespace Database\Factories;

use App\Models\Lead;
use App\Models\LeadGeoAnalysis;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeadGeoAnalysis>
 */
class LeadGeoAnalysisFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'lead_id' => Lead::factory(),
            'status' => LeadGeoAnalysis::STATUS_COMPLETED,
            'raw_data' => [
                'page_data' => [
                    'title' => $this->faker->company().' - '.$this->faker->catchPhrase(),
                    'description' => $this->faker->sentence(),
                    'canonical' => $this->faker->url(),
                    'h1_tags' => [$this->faker->sentence(4)],
                    'heading_structure' => [
                        ['level' => 1, 'text' => $this->faker->sentence(4)],
                        ['level' => 2, 'text' => $this->faker->sentence(5)],
                    ],
                    'word_count' => $this->faker->numberBetween(300, 2000),
                    'text_content' => $this->faker->paragraphs(3, true),
                    'internal_links_count' => $this->faker->numberBetween(5, 30),
                    'external_links_count' => $this->faker->numberBetween(0, 10),
                    'images_count' => $this->faker->numberBetween(2, 20),
                ],
                'robots_txt' => [
                    'exists' => true,
                    'ai_crawler_status' => [
                        'GPTBot' => 'ALLOWED',
                        'ClaudeBot' => 'ALLOWED',
                        'PerplexityBot' => 'NOT_MENTIONED',
                        'OAI-SearchBot' => 'NOT_MENTIONED',
                        'ChatGPT-User' => 'NOT_MENTIONED',
                        'anthropic-ai' => 'NOT_MENTIONED',
                        'CCBot' => 'NOT_MENTIONED',
                        'Bytespider' => 'NOT_MENTIONED',
                        'cohere-ai' => 'NOT_MENTIONED',
                        'Google-Extended' => 'ALLOWED',
                        'GoogleOther' => 'NOT_MENTIONED',
                        'Applebot-Extended' => 'NOT_MENTIONED',
                        'FacebookBot' => 'NOT_MENTIONED',
                        'Amazonbot' => 'NOT_MENTIONED',
                    ],
                    'sitemaps' => [$this->faker->url().'/sitemap.xml'],
                ],
                'llms_txt' => [
                    'llms_txt' => ['exists' => false, 'content' => ''],
                    'llms_full_txt' => ['exists' => false, 'content' => ''],
                ],
                'citability' => [
                    'average_score' => $this->faker->randomFloat(1, 35, 75),
                    'grade' => 'C',
                    'total_blocks' => $this->faker->numberBetween(4, 15),
                    'optimal_length_count' => $this->faker->numberBetween(0, 3),
                    'grade_distribution' => ['A' => 0, 'B' => 1, 'C' => 3, 'D' => 2, 'F' => 0],
                    'top_blocks' => [
                        ['heading' => $this->faker->sentence(4), 'total_score' => 68, 'word_count' => 145, 'grade' => 'B'],
                    ],
                ],
                'brand_mentions' => [
                    'wikipedia' => ['has_wikipedia_page' => false, 'wikipedia_search_results' => 0],
                    'wikidata' => ['has_wikidata_entry' => false, 'wikidata_id' => null],
                    'platform_urls' => [
                        'youtube' => 'https://www.youtube.com/results?search_query='.$this->faker->slug(),
                        'reddit' => 'https://www.reddit.com/search/?q='.$this->faker->slug(),
                        'linkedin' => 'https://www.linkedin.com/search/results/companies/?keywords='.$this->faker->slug(),
                    ],
                ],
                'schema_markup' => [
                    'detected_types' => ['Organization', 'WebSite'],
                    'count' => 2,
                ],
                'technical_seo' => [
                    'has_ssl' => true,
                    'has_ssr_content' => true,
                    'security_headers' => [
                        'Strict-Transport-Security' => true,
                        'Content-Security-Policy' => false,
                        'X-Frame-Options' => true,
                        'X-Content-Type-Options' => true,
                        'Referrer-Policy' => false,
                        'Permissions-Policy' => false,
                    ],
                    'redirect_chain' => [],
                ],
            ],
            'result' => [
                'geo_score' => $this->faker->numberBetween(40, 85),
                'ai_visibility_summary' => $this->faker->sentences(2, true),
                'citability_assessment' => $this->faker->sentence(),
                'crawler_access_summary' => $this->faker->sentence(),
                'brand_authority_assessment' => $this->faker->sentence(),
                'schema_assessment' => $this->faker->sentence(),
                'technical_assessment' => $this->faker->sentence(),
                'sales_angles' => [
                    $this->faker->sentence(),
                    $this->faker->sentence(),
                    $this->faker->sentence(),
                ],
                'quick_wins' => [
                    $this->faker->sentence(),
                    $this->faker->sentence(),
                    $this->faker->sentence(),
                ],
                'platform_recommendations' => [
                    $this->faker->sentence(),
                    $this->faker->sentence(),
                ],
            ],
            'provider' => 'openrouter',
            'model' => 'meta-llama/llama-3.1-8b-instruct:free',
            'error_message' => null,
            'started_at' => now()->subSeconds(25),
            'completed_at' => now(),
        ];
    }

    public function pending(): static
    {
        return $this->state([
            'status' => LeadGeoAnalysis::STATUS_PENDING,
            'raw_data' => null,
            'result' => null,
            'completed_at' => null,
        ]);
    }

    public function failed(): static
    {
        return $this->state([
            'status' => LeadGeoAnalysis::STATUS_FAILED,
            'raw_data' => null,
            'result' => null,
            'error_message' => 'Failed to analyse website: connection timeout.',
            'completed_at' => null,
        ]);
    }
}
