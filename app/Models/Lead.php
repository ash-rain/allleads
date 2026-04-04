<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use InvalidArgumentException;

class Lead extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_NEW = 'new';

    public const STATUS_CONTACTED = 'contacted';

    public const STATUS_REPLIED = 'replied';

    public const STATUS_CLOSED = 'closed';

    public const STATUS_DISQUALIFIED = 'disqualified';

    protected $fillable = [
        'title',
        'category',
        'address',
        'phone',
        'website',
        'email',
        'review_rating',
        'status',
        'source',
        'assignee_id',
        'import_batch_id',
    ];

    protected function casts(): array
    {
        return [
            'review_rating' => 'decimal:1',
        ];
    }

    /** Allowed status transitions (from → [allowed-to]) */
    private const STATUS_TRANSITIONS = [
        'new' => ['contacted', 'disqualified'],
        'contacted' => ['replied', 'disqualified'],
        'replied' => ['closed', 'disqualified'],
        'closed' => ['disqualified'],
        'disqualified' => [],
    ];

    // ─── Relationships ──────────────────────────────────────────────────────

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function importBatch(): BelongsTo
    {
        return $this->belongsTo(ImportBatch::class);
    }

    /** @return BelongsToMany<Tag, $this> */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(LeadNote::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(LeadActivity::class)->orderByDesc('created_at');
    }

    public function thread(): HasOne
    {
        return $this->hasOne(EmailThread::class);
    }

    /** @return HasManyThrough<EmailMessage, EmailThread, $this> */
    public function messages(): HasManyThrough
    {
        return $this->hasManyThrough(
            EmailMessage::class,
            EmailThread::class,
            'lead_id',   // FK on email_threads
            'thread_id', // FK on email_messages
        );
    }

    public function drafts(): HasMany
    {
        return $this->hasMany(EmailDraft::class);
    }

    /** @return HasOne<LeadProspectAnalysis, $this> */
    public function prospectAnalysis(): HasOne
    {
        return $this->hasOne(LeadProspectAnalysis::class);
    }

    /** @return HasOne<LeadWebsiteAnalysis, $this> */
    public function websiteAnalysis(): HasOne
    {
        return $this->hasOne(LeadWebsiteAnalysis::class);
    }

    // ─── Scopes ─────────────────────────────────────────────────────────────

    public function scopeWebDevProspects(Builder $query): Builder
    {
        return $query->where('review_rating', '>', 4.5)->whereNull('website');
    }

    public function scopeHighRating(Builder $query, float $threshold = 4.0): Builder
    {
        return $query->where('review_rating', '>=', $threshold);
    }

    public function scopeNoWebsite(Builder $query): Builder
    {
        return $query->whereNull('website');
    }

    // ─── Business Logic ─────────────────────────────────────────────────────

    public function transitionStatus(string $to, ?int $userId = null): void
    {
        $allowed = self::STATUS_TRANSITIONS[$this->status] ?? [];

        if (! in_array($to, $allowed, true)) {
            throw new InvalidArgumentException(
                "Cannot transition lead #{$this->id} from '{$this->status}' to '{$to}'."
            );
        }

        $from = $this->status;
        $this->status = $to;
        $this->save();

        LeadActivity::record($this, 'status_changed', ['from' => $from, 'to' => $to], $userId);
    }
}
