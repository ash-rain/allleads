<?php

namespace Database\Factories;

use App\Models\BusinessSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BusinessSetting>
 */
class BusinessSettingFactory extends Factory
{
    protected $model = BusinessSetting::class;

    public function definition(): array
    {
        return [
            'business_name' => 'AllLeads Web Agency',
            'website_url' => 'https://allleads.example.com',
            'industry' => 'Web Development & Digital Services',
            'company_size' => '1-10',
            'year_founded' => '2020',
            'business_description' => 'We are a web development agency specialising in building modern, high-performance websites and web applications for local businesses.',
            'key_services' => 'Custom website design & development, Website redesign, E-commerce solutions, SEO optimisation',
            'unique_selling_points' => 'Fast turnaround, personalised service, modern tech stack',
            'target_audience' => 'Local businesses, SMEs, and startups needing a professional online presence',
            'geographic_focus' => null,
            'value_proposition' => 'We help local businesses grow by building websites that attract customers and drive revenue.',
            'common_pain_points' => 'Outdated website design, slow loading times, not mobile-friendly, poor search engine visibility',
            'call_to_action' => 'Book a free 15-minute website review call',
            'social_proof' => null,
        ];
    }
}
