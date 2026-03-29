<?php

return [
    // Page / section labels
    'page_title'             => 'AI Settings',
    'page_description'       => 'Configure the AI provider and generation defaults used across all campaigns.',

    // Provider
    'field_provider'         => 'Provider',
    'provider_openrouter'    => 'OpenRouter (free models)',
    'provider_groq'          => 'Groq',
    'provider_gemini'        => 'Google Gemini',

    // Model selection
    'field_model'            => 'Model',
    'field_model_help'       => 'Available models are fetched live from the provider API.',

    // Generation settings
    'field_language'         => 'Email Language',
    'field_tone'             => 'Tone',
    'field_length'           => 'Length',
    'field_personalisation'  => 'Personalisation Level',
    'field_opener_style'     => 'Opening Line Style',
    'field_temperature'      => 'Temperature',
    'field_max_tokens'       => 'Max Tokens',

    // Tone options
    'tone_professional'      => 'Professional',
    'tone_friendly'          => 'Friendly',
    'tone_consultative'      => 'Consultative',
    'tone_direct'            => 'Direct',

    // Length options
    'length_short'           => 'Short (3–4 sentences)',
    'length_medium'          => 'Medium (1–2 paragraphs)',
    'length_long'            => 'Long (3+ paragraphs)',

    // Personalisation levels
    'personalisation_low'    => 'Low — generic template',
    'personalisation_medium' => 'Medium — mention business name',
    'personalisation_high'   => 'High — mention name, rating, category',

    // Opener styles
    'opener_question'        => 'Question',
    'opener_compliment'      => 'Compliment',
    'opener_statistic'       => 'Statistic',
    'opener_pain_point'      => 'Pain Point',

    // Include options
    'field_include_website_mention' => 'Mention missing website',
    'field_include_rating_mention'  => 'Mention their review rating',
    'field_include_cta'             => 'Include call-to-action',
    'field_custom_system_prompt'    => 'Custom System Prompt',
    'field_custom_system_prompt_help' => 'Override the built-in prompt. Use {lead_name}, {category}, {rating}, {address} as placeholders.',

    // Actions
    'action_save'            => 'Save Settings',
    'action_test'            => 'Test with Demo Lead',
    'action_fetch_models'    => 'Refresh Model List',

    // Status messages
    'saved'                  => 'AI settings saved.',
    'test_generated'         => 'Test email generated successfully.',
    'models_refreshed'       => ':count models loaded from :provider.',
    'provider_error'         => 'Could not reach :provider API. Check your API key.',
];
