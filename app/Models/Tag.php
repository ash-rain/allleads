<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    use HasFactory;

    protected $fillable = ['business_id', 'name', 'slug', 'color'];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function leads(): BelongsToMany
    {
        return $this->belongsToMany(Lead::class);
    }

    /** Whether this tag represents a business (for colour differentiation). */
    public function isBusinessTag(): bool
    {
        return Business::where('name', $this->name)->exists();
    }
}
