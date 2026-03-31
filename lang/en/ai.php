<?php

return [
    // Page / section labels
    'page_title' => 'AI Settings',
    'page_description' => 'Configure the AI provider and generation defaults used across all campaigns.',

    // Provider
    'active_provider' => 'Active Provider',
    'field_provider' => 'Provider',
    'provider_openrouter' => 'OpenRouter (free models)',
    'provider_groq' => 'Groq',
    'provider_gemini' => 'Google Gemini',

    // Model selection
    'model' => 'Model',
    'field_model' => 'Model',
    'field_model_help' => 'Available models are fetched live from the provider API.',
    'free_models_only' => 'Only free models are available on OpenRouter',

    // Generation settings
    'language' => 'AI Response Language',
    'field_language' => 'AI Response Language',
    'tone' => 'Tone',
    'field_tone' => 'Tone',
    'length' => 'Length',
    'field_length' => 'Length',
    'personalisation' => 'Personalisation Level',
    'field_personalisation' => 'Personalisation Level',
    'opener_style' => 'Opening Line Style',
    'field_opener_style' => 'Opening Line Style',
    'temperature' => 'Temperature',
    'field_temperature' => 'Temperature',
    'max_tokens' => 'Max Tokens',
    'field_max_tokens' => 'Max Tokens',

    // Tone options
    'tone_professional' => 'Professional',
    'tone_friendly' => 'Friendly',
    'tone_casual' => 'Casual',
    'tone_formal' => 'Formal',
    'tone_consultative' => 'Consultative',
    'tone_direct' => 'Direct',

    // Length options
    'length_short' => 'Short (3–4 sentences)',
    'length_medium' => 'Medium (1–2 paragraphs)',
    'length_long' => 'Long (3+ paragraphs)',

    // Personalisation levels
    'personalisation_low' => 'Low — generic template',
    'personalisation_medium' => 'Medium — mention business name',
    'personalisation_high' => 'High — mention name, rating, category',

    // Opener styles
    'opener_question' => 'Question',
    'opener_compliment' => 'Compliment',
    'opener_observation' => 'Observation',
    'opener_direct' => 'Direct',
    'opener_statistic' => 'Statistic',
    'opener_pain_point' => 'Pain Point',

    // Include options
    'include_portfolio' => 'Include portfolio mention',
    'include_audit' => 'Include audit mention',
    'include_cta' => 'Include call-to-action',
    'include_ps' => 'Include P.S.',
    'custom_system_prompt' => 'Custom System Prompt',
    'custom_prompt_help' => 'Override the built-in prompt. Use {lead_name}, {category}, {rating}, {address} as placeholders.',
    'field_include_website_mention' => 'Mention missing website',
    'field_include_rating_mention' => 'Mention their review rating',
    'field_include_cta' => 'Include call-to-action',
    'field_custom_system_prompt' => 'Custom System Prompt',
    'field_custom_system_prompt_help' => 'Override the built-in prompt. Use {lead_name}, {category}, {rating}, {address} as placeholders.',

    // Actions
    'action_save' => 'Save Settings',
    'action_test' => 'Test with Demo Lead',
    'action_fetch_models' => 'Refresh Model List',
    'refresh_openrouter_models' => 'Refresh OpenRouter Models',
    'refresh_groq_models' => 'Refresh Groq Models',

    // Status messages
    'saved' => 'AI settings saved.',
    'test_generated' => 'Test email generated successfully.',
    'models_refreshed' => ':count models loaded from :provider.',
    'models_refresh_failed' => 'Failed to refresh models. Please try again.',
    'provider_error' => 'Could not reach :provider API. Check your API key.',
    'refine_dispatched' => 'Refinement queued. The draft will update here automatically.',
    'refine_complete' => 'Draft updated with AI refinement.',
];
