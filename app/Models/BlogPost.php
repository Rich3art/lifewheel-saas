<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class BlogPost extends Model
{
    protected $fillable = [
        'title', 'slug', 'excerpt', 'body', 'status', 'featured_image_path',
        'author_id', 'seo_title', 'meta_description', 'canonical_url',
        'open_graph', 'published_at',
    ];

    protected function casts(): array
    {
        return [
            'open_graph' => 'array',
            'published_at' => 'datetime',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(BlogCategory::class, 'blog_category_post')->withTimestamps();
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(BlogTag::class, 'blog_post_tag')->withTimestamps();
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(BlogPostRevision::class);
    }

    public function isPublished(): bool
    {
        return $this->status === 'published' && ($this->published_at === null || $this->published_at->isPast());
    }
}
