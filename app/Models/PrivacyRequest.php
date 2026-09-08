<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class PrivacyRequest extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'status',
        'details',
        'identity_confirmed_by_user_at',
        'identity_confirmed_at',
        'due_at',
        'completed_at',
        'processed_by',
        'admin_notes',
        'resolution_summary',
    ];

    protected function casts(): array
    {
        return [
            'identity_confirmed_by_user_at' => 'datetime',
            'identity_confirmed_at' => 'datetime',
            'due_at' => 'datetime',
            'completed_at' => 'datetime',
            'resolution_summary' => 'array',
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

    public function dataExports(): HasMany
    {
        return $this->hasMany(DataExport::class);
    }
}
