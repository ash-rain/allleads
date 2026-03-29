<?php

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Seeder;

class TagSeeder extends Seeder
{
    public function run(): void
    {
        $tags = [
            ['name' => 'Hot Lead',           'slug' => 'hot-lead',           'color' => '#ef4444'],
            ['name' => 'No Website',         'slug' => 'no-website',         'color' => '#f97316'],
            ['name' => 'High Rating',        'slug' => 'high-rating',        'color' => '#eab308'],
            ['name' => 'Web Dev Prospect',   'slug' => 'web-dev-prospect',   'color' => '#22c55e'],
            ['name' => 'Called',             'slug' => 'called',             'color' => '#3b82f6'],
            ['name' => 'Emailed',            'slug' => 'emailed',            'color' => '#8b5cf6'],
        ];

        foreach ($tags as $tag) {
            Tag::firstOrCreate(['slug' => $tag['slug']], $tag);
        }
    }
}
