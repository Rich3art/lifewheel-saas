<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Package extends Model
{
    protected $fillable = [
        'name', 'slug', 'description', 'short_description', 'active', 'public',
        'featured', 'price_cents', 'currency', 'billing_interval', 'trial_days',
        'sort_order', 'cta_label', 'landing_page_slug', 'seo',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'public' => 'boolean',
            'featured' => 'boolean',
            'seo' => 'array',
        ];
    }

    public function features(): BelongsToMany
    {
        return $this->belongsToMany(Feature::class)->withPivot('enabled')->withTimestamps();
    }

    public function limits(): HasMany
    {
        return $this->hasMany(PackageLimit::class);
    }

    public function providerMappings(): HasMany
    {
        return $this->hasMany(PackageProviderMapping::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }
}
