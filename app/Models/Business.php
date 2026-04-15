<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Business extends Model
{
    use HasFactory;

    protected $fillable = [
        // Identity
        'name',
        'website_url',
        'industry',
        'company_size',
        'year_founded',
        // What we do
        'description',
        'key_services',
        'unique_selling_points',
        // Target market
        'target_audience',
        'geographic_focus',
        // Sales context
        'value_proposition',
        'common_pain_points',
        'call_to_action',
        'social_proof',
        // Tag styling
        'tag_color',
    ];

    // ─── Relationships ──────────────────────────────────────────────────────

    /** @return BelongsToMany<User, $this> */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot('role')->withTimestamps();
    }

    /** @return HasMany<Lead, $this> */
    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    /** @return HasMany<Tag, $this> */
    public function tags(): HasMany
    {
        return $this->hasMany(Tag::class);
    }

    /** @return HasMany<ImportBatch, $this> */
    public function importBatches(): HasMany
    {
        return $this->hasMany(ImportBatch::class);
    }

    // ─── Helpers ────────────────────────────────────────────────────────────

    /** Whether the essential fields are configured. */
    public function isConfigured(): bool
    {
        return filled($this->name) && filled($this->description);
    }

    /** Format business profile into a prompt-ready context block for AI jobs. */
    public function toPromptContext(): string
    {
        if (! $this->isConfigured()) {
            return 'You are targeting B2B prospects for outreach.';
        }

        $context = $this->buildPromptContext();

        // Groq enforces a strict request-size limit; cap context to avoid 413 errors.
        if (AiSetting::singleton()->provider === 'groq') {
            return mb_substr($context, 0, 800);
        }

        return $context;
    }

    /** Return the shared global AI settings. */
    public function aiSettingOrCreate(): AiSetting
    {
        return AiSetting::singleton();
    }

    private function buildPromptContext(): string
    {
        $lines = [
            'OUR BUSINESS PROFILE:',
            'Business: '.$this->name,
        ];

        $fields = [
            'industry' => 'Industry',
            'description' => 'Description',
            'key_services' => 'Services',
            'unique_selling_points' => 'USPs',
            'target_audience' => 'Target audience',
            'geographic_focus' => 'Geographic focus',
            'value_proposition' => 'Value proposition',
            'common_pain_points' => 'Problems we solve',
            'call_to_action' => 'Default CTA',
            'social_proof' => 'Social proof',
        ];

        foreach ($fields as $attribute => $label) {
            if (filled($this->$attribute)) {
                $lines[] = "{$label}: {$this->$attribute}";
            }
        }

        return implode("\n", $lines);
    }
}
