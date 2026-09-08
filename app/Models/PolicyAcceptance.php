<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PolicyAcceptance extends Model
{
    protected $fillable = [
        'user_id',
        'page_id',
        'page_version_id',
        'accepted_at',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return ['accepted_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function pageVersion(): BelongsTo
    {
        return $this->belongsTo(PageVersion::class);
    }
}
