<?php

namespace Database\Factories;

use App\Models\AiSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AiSetting>
 */
class AiSettingFactory extends Factory
{
    protected $model = AiSetting::class;

    public function definition(): array
    {
        return [
            'provider' => 'openrouter',
            'model' => 'meta-llama/llama-3.1-8b-instruct:free',
            'language' => 'Bulgarian',
            'tone' => 'professional',
            'length' => 'medium',
            'personalisation' => 'medium',
            'opener_style' => 'question',
            'temperature' => 0.70,
            'max_tokens' => 3000,
            'timeout' => 90,
            'include_portfolio' => false,
            'include_audit' => false,
            'include_cta' => true,
            'include_ps' => false,
        ];
    }
}
