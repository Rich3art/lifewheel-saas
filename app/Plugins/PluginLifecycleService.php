<?php

namespace App\Plugins;

use App\Models\InstalledPlugin;
use App\Services\AuditLogger;
use RuntimeException;

final class PluginLifecycleService
{
    public function __construct(
        private readonly PluginRegistry $registry,
        private readonly AuditLogger $audit,
    ) {
    }

    public function install(string $pluginId): InstalledPlugin
    {
        $discovered = $this->mustFind($pluginId);
        $manifest = $discovered['manifest'];
        $context = new PluginContext($manifest, $discovered['base_path']);

        $this->assertCompatible($manifest);
        $plugin = $this->instantiate($context);
        $plugin->install($context);

        $record = InstalledPlugin::query()->updateOrCreate(
            ['plugin_id' => $manifest->id],
            [
                'name' => $manifest->name,
                'version' => $manifest->version,
                'author' => $manifest->author,
                'description' => $manifest->description,
                'path' => $discovered['base_path'],
                'status' => 'disabled',
                'manifest' => $manifest->raw,
                'installed_at' => now(),
            ],
        );

        $this->syncRegistrations($record, $manifest);
        $this->audit->log('plugin.installed', request()->user(), $record);

        return $record;
    }

    public function activate(string $pluginId): InstalledPlugin
    {
        $record = InstalledPlugin::query()->findOrFail($pluginId);
        $discovered = $this->mustFind($pluginId);
        $manifest = $discovered['manifest'];

        $this->assertCompatible($manifest);
        $this->assertDependenciesEnabled($manifest);

        $context = new PluginContext($manifest, $discovered['base_path']);
        $this->instantiate($context)->activate($context);

        $record->forceFill([
            'status' => 'enabled',
            'version' => $manifest->version,
            'manifest' => $manifest->raw,
            'activated_at' => now(),
            'deactivated_at' => null,
        ])->save();

        $this->syncRegistrations($record, $manifest);
        $this->audit->log('plugin.activated', request()->user(), $record);

        return $record;
    }

    public function deactivate(string $pluginId): InstalledPlugin
    {
        $record = InstalledPlugin::query()->findOrFail($pluginId);
        $discovered = $this->mustFind($pluginId);
        $dependents = InstalledPlugin::query()
            ->where('status', 'enabled')
            ->where('plugin_id', '!=', $pluginId)
            ->get()
            ->filter(fn (InstalledPlugin $plugin): bool => in_array($pluginId, $plugin->manifest['dependencies'] ?? [], true));

        if ($dependents->isNotEmpty()) {
            throw new RuntimeException('Cannot deactivate plugin because another enabled plugin depends on it.');
        }

        $context = new PluginContext($discovered['manifest'], $discovered['base_path']);
        $this->instantiate($context)->deactivate($context);

        $record->forceFill([
            'status' => 'disabled',
            'deactivated_at' => now(),
        ])->save();

        $this->audit->log('plugin.deactivated', request()->user(), $record);

        return $record;
    }

    public function upgrade(string $pluginId): InstalledPlugin
    {
        $record = InstalledPlugin::query()->findOrFail($pluginId);
        $discovered = $this->mustFind($pluginId);
        $manifest = $discovered['manifest'];
        $fromVersion = $record->version;
        $context = new PluginContext($manifest, $discovered['base_path']);

        $this->assertCompatible($manifest);
        $this->instantiate($context)->upgrade($context, $fromVersion);

        $record->forceFill([
            'name' => $manifest->name,
            'version' => $manifest->version,
            'author' => $manifest->author,
            'description' => $manifest->description,
            'manifest' => $manifest->raw,
        ])->save();

        $this->syncRegistrations($record, $manifest);
        $this->audit->log('plugin.upgraded', request()->user(), $record, ['from_version' => $fromVersion, 'to_version' => $manifest->version]);

        return $record;
    }

    public function uninstall(string $pluginId, bool $removeData = false): void
    {
        $record = InstalledPlugin::query()->findOrFail($pluginId);
        $discovered = $this->mustFind($pluginId);
        $context = new PluginContext($discovered['manifest'], $discovered['base_path']);

        if ($record->isEnabled()) {
            $this->deactivate($pluginId);
        }

        $this->instantiate($context)->uninstall($context, $removeData);
        $record->delete();
        $this->audit->log('plugin.uninstalled', request()->user(), null, ['plugin_id' => $pluginId, 'remove_data' => $removeData]);
    }

    public function instantiate(PluginContext $context): object
    {
        $entry = realpath($context->path($context->manifest->entry));
        $basePath = realpath($context->basePath);

        if (! $entry || ! $basePath || ! str_starts_with($entry, $basePath.DIRECTORY_SEPARATOR)) {
            throw new RuntimeException('Plugin entry must resolve inside the plugin directory.');
        }

        require_once $entry;

        $class = $context->manifest->raw['class'] ?? null;

        if (! is_string($class) || ! class_exists($class)) {
            throw new RuntimeException('Plugin class is missing or cannot be loaded.');
        }

        $plugin = app($class);

        if (! $plugin instanceof Contracts\Plugin) {
            throw new RuntimeException('Plugin class must implement the LifeWheel plugin contract.');
        }

        return $plugin;
    }

    private function mustFind(string $pluginId): array
    {
        return $this->registry->find($pluginId) ?? throw new RuntimeException("Plugin [{$pluginId}] was not discovered.");
    }

    private function assertCompatible(PluginManifest $manifest): void
    {
        if (! version_compare(PHP_VERSION, ltrim($manifest->php, '^>=<~ '), '>=')) {
            throw new RuntimeException("Plugin [{$manifest->id}] requires PHP {$manifest->php}.");
        }

        $coreVersion = config('plugins.core_version', '0.4.0');

        if (! $this->satisfiesVersion($coreVersion, $manifest->coreVersion)) {
            throw new RuntimeException("Plugin [{$manifest->id}] requires core {$manifest->coreVersion}.");
        }
    }

    private function assertDependenciesEnabled(PluginManifest $manifest): void
    {
        foreach ($manifest->dependencies as $dependency) {
            $enabled = InstalledPlugin::query()
                ->where('plugin_id', $dependency)
                ->where('status', 'enabled')
                ->exists();

            if (! $enabled) {
                throw new RuntimeException("Plugin [{$manifest->id}] requires enabled dependency [{$dependency}].");
            }
        }
    }

    private function syncRegistrations(InstalledPlugin $record, PluginManifest $manifest): void
    {
        $record->permissions()->delete();
        $record->features()->delete();
        $record->menus()->delete();
        $record->settingsSections()->delete();

        foreach ($manifest->permissions as $permission) {
            $record->permissions()->create(['slug' => $permission['slug'] ?? $permission, 'manifest' => $permission]);
        }

        foreach ($manifest->features as $feature) {
            $record->features()->create(['slug' => $feature['slug'] ?? $feature, 'manifest' => $feature]);
        }

        foreach ($manifest->menus as $menu) {
            $record->menus()->create(['slug' => $menu['slug'] ?? $menu['label'] ?? 'menu', 'manifest' => $menu]);
        }

        foreach ($manifest->settingsSections as $section) {
            $record->settingsSections()->create(['slug' => $section['slug'] ?? $section['label'] ?? 'settings', 'manifest' => $section]);
        }
    }

    private function satisfiesVersion(string $actual, string $constraint): bool
    {
        if (str_starts_with($constraint, '^')) {
            $minimum = substr($constraint, 1);

            return version_compare($actual, $minimum, '>=');
        }

        if (str_starts_with($constraint, '>=')) {
            return version_compare($actual, substr($constraint, 2), '>=');
        }

        return version_compare($actual, $constraint, '==');
    }
}
