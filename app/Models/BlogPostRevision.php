<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class BlogPostRevision extends Model
{
    protected $fillable = [
        'blog_post_id', 'title', 'excerpt', 'body', 'status', 'seo_title',
        'meta_description', 'canonical_url', 'open_graph', 'created_by',
    ];

    protected function casts(): array
    {
        return ['open_graph' => 'array'];
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(BlogPost::class, 'blog_post_id');
    }
}
