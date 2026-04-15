<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\User;
use Illuminate\Database\Seeder;

class BusinessSeeder extends Seeder
{
    public function run(): void
    {
        $business = Business::firstOrCreate(
            ['name' => 'AllLeads Web Agency'],
            [
                'industry' => 'Web Development & Digital Services',
                'company_size' => '1-10',
                'description' => 'We are a web development agency specialising in building modern, high-performance websites and web applications for local businesses.',
                'key_services' => 'Custom website design & development, Website redesign & modernisation, E-commerce solutions, SEO optimisation, Website maintenance & support',
                'unique_selling_points' => 'Fast turnaround, personalised service, modern tech stack, ongoing support included',
                'target_audience' => 'Local businesses, SMEs, and startups that need a professional online presence',
                'value_proposition' => 'We help local businesses grow by building websites that attract customers and drive revenue.',
                'common_pain_points' => 'Outdated website design, slow loading times, not mobile-friendly, poor search engine visibility',
                'call_to_action' => 'Book a free 15-minute website review call',
                'tag_color' => '#3b82f6',
            ]
        );

        // Attach all existing users as owners of the default business
        $userIds = User::pluck('id');
        foreach ($userIds as $userId) {
            $business->users()->syncWithoutDetaching([$userId => ['role' => 'owner']]);
        }
    }
}
