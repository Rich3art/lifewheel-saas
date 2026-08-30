<?php

namespace App\Plugins;

use App\Models\InstalledPlugin;
use Throwable;

final class PluginRegistry
{
    public function __construct(
        private readonly string $pluginPath,
    ) {
    }

    public static function default(): self
    {
        return new self(config('plugins.path', base_path('plugins')));
    }

    public function discover(): array
    {
        if (! is_dir($this->pluginPath)) {
            return [];
        }

        $plugins = [];

        foreach (glob($this->pluginPath.DIRECTORY_SEPARATOR.'*'.DIRECTORY_SEPARATOR.'plugin.json') ?: [] as $manifestPath) {
            $manifest = $this->manifestFromPath($manifestPath);
            $plugins[$manifest->id] = [
                'manifest' => $manifest,
                'base_path' => dirname($manifestPath),
            ];
        }

        ksort($plugins);

        return $plugins;
    }

    public function enabled(): array
    {
        try {
            $enabledIds = InstalledPlugin::query()
                ->where('status', 'enabled')
                ->pluck('plugin_id')
                ->all();
        } catch (Throwable) {
            return [];
        }

        return array_intersect_key($this->discover(), array_flip($enabledIds));
    }

    public function find(string $pluginId): ?array
    {
        return $this->discover()[$pluginId] ?? null;
    }

    private function manifestFromPath(string $manifestPath): PluginManifest
    {
        $data = json_decode((string) file_get_contents($manifestPath), true, flags: JSON_THROW_ON_ERROR);

        return PluginManifest::fromArray($data);
    }
}
