<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class PaymentProvider extends Model
{
    protected $fillable = ['key', 'name', 'enabled', 'sandbox', 'settings', 'last_checked_at'];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'sandbox' => 'boolean',
            'settings' => 'encrypted:array',
            'last_checked_at' => 'datetime',
        ];
    }

    public function mappings(): HasMany
    {
        return $this->hasMany(PackageProviderMapping::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }
}
