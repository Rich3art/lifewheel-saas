<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Page extends Model
{
    protected $fillable = [
        'title', 'slug', 'status', 'body', 'seo_title', 'meta_description',
        'canonical_url', 'open_graph', 'is_legal', 'current_version_id', 'published_at',
    ];

    protected function casts(): array
    {
        return [
            'open_graph' => 'array',
            'is_legal' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function versions(): HasMany
    {
        return $this->hasMany(PageVersion::class);
    }

    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(PageVersion::class, 'current_version_id');
    }

    public function isPublished(): bool
    {
        return $this->status === 'published' && ($this->published_at === null || $this->published_at->isPast());
    }
}
