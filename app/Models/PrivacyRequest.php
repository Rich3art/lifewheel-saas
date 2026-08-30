<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PrivacyRequest extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'status',
        'details',
        'identity_confirmed_at',
        'completed_at',
        'processed_by',
        'admin_notes',
    ];

    protected function casts(): array
    {
        return [
            'identity_confirmed_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}
