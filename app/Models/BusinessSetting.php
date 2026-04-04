<?php

namespace App\Models;

use Database\Factories\BusinessSettingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessSetting extends Model
{
    /** @use HasFactory<BusinessSettingFactory> */
    use HasFactory;

    protected $fillable = [
        'business_name',
        'website_url',
        'industry',
        'company_size',
        'year_founded',
        'business_description',
        'key_services',
        'unique_selling_points',
        'target_audience',
        'geographic_focus',
        'value_proposition',
        'common_pain_points',
        'call_to_action',
        'social_proof',
    ];

    /** Retrieve the singleton row, creating web-agency defaults if absent. */
    public static function singleton(): self
    {
        return self::firstOrCreate([], [
            'business_name' => 'AllLeads Web Agency',
            'industry' => 'Web Development & Digital Services',
            'company_size' => '1-10',
            'business_description' => 'We are a web development agency specialising in building modern, high-performance websites and web applications for local businesses. We help companies establish a strong online presence with custom design, development, and ongoing support.',
            'key_services' => 'Custom website design & development, Website redesign & modernisation, E-commerce solutions, SEO optimisation, Website maintenance & support, Landing pages & conversion optimisation',
            'unique_selling_points' => 'Fast turnaround, personalised service, modern tech stack, ongoing support included',
            'target_audience' => 'Local businesses, SMEs, and startups that need a professional online presence or want to upgrade their existing website',
            'value_proposition' => 'We help local businesses grow by building websites that attract customers and drive revenue, with transparent pricing and hands-on support.',
            'common_pain_points' => 'Outdated website design, slow loading times, not mobile-friendly, poor search engine visibility, no online booking or contact forms, difficulty updating content',
            'call_to_action' => 'Book a free 15-minute website review call',
        ]);
    }

    /** Whether the essential fields are configured. */
    public function isConfigured(): bool
    {
        return filled($this->business_name) && filled($this->business_description);
    }

    /** Format business profile into a prompt-ready context block for AI jobs. */
    public function toPromptContext(): string
    {
        if (! $this->isConfigured()) {
            return 'You are targeting B2B prospects for outreach.';
        }

        $lines = [
            'OUR BUSINESS PROFILE:',
            'Business: '.$this->business_name,
        ];

        if (filled($this->industry)) {
            $lines[] = 'Industry: '.$this->industry;
        }

        if (filled($this->business_description)) {
            $lines[] = 'Description: '.$this->business_description;
        }

        if (filled($this->key_services)) {
            $lines[] = 'Services: '.$this->key_services;
        }

        if (filled($this->unique_selling_points)) {
            $lines[] = 'USPs: '.$this->unique_selling_points;
        }

        if (filled($this->target_audience)) {
            $lines[] = 'Target audience: '.$this->target_audience;
        }

        if (filled($this->geographic_focus)) {
            $lines[] = 'Geographic focus: '.$this->geographic_focus;
        }

        if (filled($this->value_proposition)) {
            $lines[] = 'Value proposition: '.$this->value_proposition;
        }

        if (filled($this->common_pain_points)) {
            $lines[] = 'Problems we solve: '.$this->common_pain_points;
        }

        if (filled($this->call_to_action)) {
            $lines[] = 'Default CTA: '.$this->call_to_action;
        }

        if (filled($this->social_proof)) {
            $lines[] = 'Social proof: '.$this->social_proof;
        }

        return implode("\n", $lines);
    }
}
