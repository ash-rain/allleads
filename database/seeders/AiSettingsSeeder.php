<?php

namespace Database\Seeders;

use App\Models\AiSetting;
use Illuminate\Database\Seeder;

class AiSettingsSeeder extends Seeder
{
    public function run(): void
    {
        AiSetting::firstOrCreate([], [
            'provider'        => 'openrouter',
            'model'           => 'mistralai/mistral-7b-instruct:free',
            'language'        => 'English',
            'tone'            => 'professional',
            'length'          => 'medium',
            'personalisation' => 'medium',
            'opener_style'    => 'question',
            'include_portfolio' => false,
            'include_audit'     => false,
            'include_cta'       => true,
            'include_ps'        => false,
            'temperature'       => 0.70,
            'max_tokens'        => 1024,
        ]);
    }
}
