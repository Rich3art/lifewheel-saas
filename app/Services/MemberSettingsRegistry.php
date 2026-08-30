<?php

namespace App\Services;

use App\Models\MemberSettingsSection;
use Illuminate\Support\Collection;

final class MemberSettingsRegistry
{
    /**
     * @return Collection<int, MemberSettingsSection>
     */
    public function visibleSections(): Collection
    {
        return MemberSettingsSection::query()
            ->where('enabled', true)
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get();
    }

    public function isVisible(string $key): bool
    {
        return MemberSettingsSection::query()
            ->where('key', $key)
            ->where('enabled', true)
            ->exists();
    }
}
