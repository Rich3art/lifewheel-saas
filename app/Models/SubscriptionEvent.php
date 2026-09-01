<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class SubscriptionEvent extends Model
{
    protected $fillable = [
        'subscription_id',
        'user_id',
        'provider_key',
        'event_type',
        'external_event_id',
        'payload_summary',
        'processed_at',
    ];

    protected function casts(): array
    {
        return ['payload_summary' => 'array', 'processed_at' => 'datetime'];
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
