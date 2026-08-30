<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PageVersion extends Model
{
    protected $fillable = [
        'page_id', 'version', 'title', 'body', 'seo_title', 'meta_description',
        'canonical_url', 'open_graph', 'status', 'published_at', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'open_graph' => 'array',
            'published_at' => 'datetime',
        ];
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }
}
