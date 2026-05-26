<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Playbook extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'name',
        'description',
        'icon',
        'filters',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'filters' => 'array',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    // ─── Relationships ──────────────────────────────────────────────────────

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    // ─── Query Modification ─────────────────────────────────────────────────

    public function applyToQuery(Builder $query): Builder
    {
        $filters = $this->filters ?? [];

        if (! empty($filters['no_website'])) {
            $query->noWebsite();
        }

        if (isset($filters['has_email']) && $filters['has_email'] === true) {
            $query->whereNotNull('email');
        }

        if (isset($filters['rating_min']) && is_numeric($filters['rating_min'])) {
            $query->highRating((float) $filters['rating_min']);
        }

        if (! empty($filters['categories']) && is_array($filters['categories'])) {
            $query->whereIn('category', $filters['categories']);
        }

        if (! empty($filters['tags']) && is_array($filters['tags'])) {
            $query->whereHas('tags', fn ($q) => $q->whereIn('tags.id', $filters['tags']));
        }

        return $query;
    }

    // ─── Helpers ────────────────────────────────────────────────────────────

    public function filterSummary(): string
    {
        $filters = $this->filters ?? [];
        $parts = [];

        if (! empty($filters['no_website'])) {
            $parts[] = __('playbooks.filter_no_website');
        }

        if (isset($filters['has_email']) && $filters['has_email'] === true) {
            $parts[] = __('playbooks.filter_has_email');
        }

        if (isset($filters['rating_min']) && is_numeric($filters['rating_min'])) {
            $parts[] = __('playbooks.filter_rating_min_summary', ['min' => $filters['rating_min']]);
        }

        if (! empty($filters['categories']) && is_array($filters['categories'])) {
            $parts[] = implode(', ', $filters['categories']);
        }

        return implode(' · ', $parts) ?: '—';
    }
}
