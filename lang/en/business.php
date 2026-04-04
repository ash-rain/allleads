<?php

return [
    'page_title' => 'Business Settings',
    'page_description' => 'Configure your business profile. This context is injected into every AI-generated email and analysis.',

    // Section headings
    'section_identity' => 'Company Identity',
    'section_identity_description' => 'Basic information about your company.',
    'section_what_we_do' => 'What We Do',
    'section_what_we_do_description' => 'Describe your services and what makes you different.',
    'section_target_market' => 'Target Market',
    'section_target_market_description' => 'Define who you sell to and where.',
    'section_sales_context' => 'Sales Context',
    'section_sales_context_description' => 'The core pitch and proof points used in outreach.',

    // Fields — Company Identity
    'business_name' => 'Business Name',
    'website_url' => 'Website URL',
    'industry' => 'Industry / Sector',
    'company_size' => 'Company Size',
    'year_founded' => 'Year Founded',

    // Fields — What We Do
    'business_description' => 'Business Description',
    'business_description_help' => 'A 2-3 sentence overview of what your business does.',
    'key_services' => 'Key Services / Products',
    'key_services_help' => 'List your main offerings. One per line or comma-separated.',
    'unique_selling_points' => 'Unique Selling Points',
    'unique_selling_points_help' => 'What sets you apart from competitors.',

    // Fields — Target Market
    'target_audience' => 'Target Audience',
    'target_audience_help' => 'Describe the ideal customers you sell to.',
    'geographic_focus' => 'Geographic Focus',
    'geographic_focus_help' => 'Region, city, or country you operate in. Leave blank for global.',

    // Fields — Sales Context
    'value_proposition' => 'Value Proposition',
    'value_proposition_help' => 'Your core pitch in 1-2 sentences.',
    'common_pain_points' => 'Common Pain Points',
    'common_pain_points_help' => 'Problems your prospects typically have that you solve.',
    'call_to_action' => 'Default Call to Action',
    'call_to_action_help' => 'The default CTA used at the end of outreach emails.',
    'social_proof' => 'Social Proof',
    'social_proof_help' => 'Brief proof points: testimonials, notable clients, stats.',

    // Company size options
    'size_1_10' => '1–10 employees',
    'size_11_50' => '11–50 employees',
    'size_51_200' => '51–200 employees',
    'size_201_plus' => '201+ employees',

    // Actions
    'generate_from_website' => 'Generate from Website',
    'generate_from_website_description' => 'Enter your website URL and we\'ll scrape it and use AI to auto-fill your business profile.',
    'generate_from_website_url_label' => 'Your Website URL',
    'generate_from_website_url_placeholder' => 'https://yourcompany.com',
    'generating' => 'Scraping & analysing…',
    'generated_success' => 'Business profile generated — review and save.',
    'generated_error' => 'Could not generate profile from website.',
];
