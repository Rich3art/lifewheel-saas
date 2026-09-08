<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PrivacyConsent extends Model
{
    protected $fillable = ['user_id', 'key', 'granted', 'granted_at', 'revoked_at', 'metadata'];

    protected function casts(): array
    {
        return [
            'granted' => 'boolean',
            'granted_at' => 'datetime',
            'revoked_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
