<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\Playbook;
use Illuminate\Database\Seeder;

class PlaybookSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            [
                'name' => 'Web Dev Prospects',
                'description' => 'Businesses without a website and high rating — prime web dev targets.',
                'filters' => ['no_website' => true, 'rating_min' => 4.5],
                'sort_order' => 1,
            ],
            [
                'name' => 'High-Value No Website',
                'description' => 'Good-rated businesses with no online presence.',
                'filters' => ['no_website' => true, 'rating_min' => 4.0],
                'sort_order' => 2,
            ],
            [
                'name' => 'Email-Ready Leads',
                'description' => 'Leads with an email address and solid rating.',
                'filters' => ['has_email' => true, 'rating_min' => 4.0],
                'sort_order' => 3,
            ],
            [
                'name' => 'Quick Wins',
                'description' => 'Has email, no website, high rating — easy outreach with strong pitch.',
                'filters' => ['has_email' => true, 'no_website' => true, 'rating_min' => 4.5],
                'sort_order' => 4,
            ],
            [
                'name' => 'Local Gems',
                'description' => 'Near-perfect rated businesses.',
                'filters' => ['rating_min' => 4.8],
                'sort_order' => 5,
            ],
            [
                'name' => 'Contactable Leads',
                'description' => 'Any lead that has an email address.',
                'filters' => ['has_email' => true],
                'sort_order' => 6,
            ],
            [
                'name' => 'No Online Presence',
                'description' => 'Businesses with no website.',
                'filters' => ['no_website' => true],
                'sort_order' => 7,
            ],
        ];

        foreach (Business::all() as $business) {
            foreach ($defaults as $data) {
                Playbook::firstOrCreate(
                    ['business_id' => $business->id, 'name' => $data['name']],
                    array_merge($data, ['business_id' => $business->id, 'is_active' => true])
                );
            }
        }
    }
}
