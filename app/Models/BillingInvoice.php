<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class BillingInvoice extends Model
{
    protected $fillable = [
        'subscription_id',
        'user_id',
        'provider_key',
        'external_invoice_id',
        'status',
        'amount_cents',
        'currency',
        'paid_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return ['paid_at' => 'datetime', 'metadata' => 'array'];
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }
}
