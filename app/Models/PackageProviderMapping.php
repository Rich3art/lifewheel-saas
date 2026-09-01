<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PackageProviderMapping extends Model
{
    protected $fillable = [
        'package_id',
        'payment_provider_id',
        'external_product_id',
        'external_price_id',
        'currency',
        'amount_cents',
        'active',
        'metadata',
    ];

    protected function casts(): array
    {
        return ['active' => 'boolean', 'metadata' => 'array'];
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(PaymentProvider::class, 'payment_provider_id');
    }
}
