<?php

namespace App\Services;

use App\Models\Feature;
use App\Models\User;

final class EntitlementService
{
    public function userHasFeature(User $user, string $featureSlug): bool
    {
        $feature = Feature::query()->where('slug', $featureSlug)->where('active', true)->first();

        if (! $feature) {
            return false;
        }

        $override = $user->featureOverrides()->where('feature_id', $feature->id)->first();

        if ($override) {
            return $override->enabled;
        }

        return $user->packages()
            ->wherePivot('status', 'active')
            ->where(function ($query): void {
                $query->wherePivotNull('ends_at')->orWherePivot('ends_at', '>', now());
            })
            ->whereHas('features', function ($query) use ($featureSlug): void {
                $query->where('slug', $featureSlug)->where('feature_package.enabled', true);
            })
            ->exists();
    }

    public function limitFor(User $user, string $key): ?string
    {
        $package = $user->packages()
            ->wherePivot('status', 'active')
            ->where(function ($query): void {
                $query->wherePivotNull('ends_at')->orWherePivot('ends_at', '>', now());
            })
            ->with('limits')
            ->orderByDesc('user_packages.created_at')
            ->first();

        return $package?->limits->firstWhere('key', $key)?->value;
    }
}
