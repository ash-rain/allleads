<?php

namespace Database\Factories;

use App\Models\Business;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Business>
 */
class BusinessFactory extends Factory
{
    protected $model = Business::class;

    public function definition(): array
    {
        return [
            'name' => 'AllLeads Web Agency',
            'website_url' => 'https://allleads.example.com',
            'industry' => 'Web Development & Digital Services',
            'company_size' => '1-10',
            'year_founded' => '2020',
            'description' => 'We are a web development agency specialising in building modern, high-performance websites and web applications for local businesses.',
            'key_services' => 'Custom website design & development, Website redesign, E-commerce solutions, SEO optimisation',
            'unique_selling_points' => 'Fast turnaround, personalised service, modern tech stack',
            'target_audience' => 'Local businesses, SMEs, and startups needing a professional online presence',
            'geographic_focus' => null,
            'value_proposition' => 'We help local businesses grow by building websites that attract customers and drive revenue.',
            'common_pain_points' => 'Outdated website design, slow loading times, not mobile-friendly, poor search engine visibility',
            'call_to_action' => 'Book a free 15-minute website review call',
            'social_proof' => null,
            'tag_color' => '#3b82f6',
        ];
    }
}
