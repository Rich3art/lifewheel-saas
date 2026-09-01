<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Subscription extends Model
{
    protected $fillable = [
        'user_id',
        'package_id',
        'payment_provider_id',
        'provider_key',
        'external_customer_id',
        'external_subscription_id',
        'status',
        'amount_cents',
        'currency',
        'billing_interval',
        'trial',
        'current_period_starts_at',
        'current_period_ends_at',
        'cancels_at',
        'cancelled_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'trial' => 'boolean',
            'current_period_starts_at' => 'datetime',
            'current_period_ends_at' => 'datetime',
            'cancels_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(PaymentProvider::class, 'payment_provider_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(SubscriptionEvent::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(BillingInvoice::class);
    }
}
