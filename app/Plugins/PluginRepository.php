<?php

namespace App\Plugins;

use App\Models\InstalledPlugin;

final class PluginRepository
{
    public function installed(): array
    {
        return InstalledPlugin::query()
            ->with(['permissions', 'features', 'menus', 'settingsSections'])
            ->orderBy('name')
            ->get()
            ->all();
    }

    public function enabledMenus(string $location): array
    {
        return InstalledPlugin::query()
            ->where('status', 'enabled')
            ->with('menus')
            ->get()
            ->flatMap(fn (InstalledPlugin $plugin) => $plugin->menus)
            ->filter(fn ($menu) => ($menu->manifest['location'] ?? null) === $location)
            ->sortBy(fn ($menu) => $menu->manifest['sort'] ?? 100)
            ->values()
            ->all();
    }

    public function enabledSettingsSections(string $audience): array
    {
        return InstalledPlugin::query()
            ->where('status', 'enabled')
            ->with('settingsSections')
            ->get()
            ->flatMap(fn (InstalledPlugin $plugin) => $plugin->settingsSections)
            ->filter(fn ($section) => ($section->manifest['audience'] ?? null) === $audience)
            ->sortBy(fn ($section) => $section->manifest['sort'] ?? 100)
            ->values()
            ->all();
    }
}
