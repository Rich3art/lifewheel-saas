<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class AiUsageEvent extends Model
{
    protected $fillable = [
        'user_id',
        'feature_slug',
        'provider_key',
        'model',
        'input_tokens',
        'output_tokens',
        'estimated_cost_cents',
        'status',
        'request_hash',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
