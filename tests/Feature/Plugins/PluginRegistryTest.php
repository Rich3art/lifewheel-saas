<?php

namespace Tests\Feature\Plugins;

use App\Models\InstalledPlugin;
use App\Plugins\PluginRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PluginRegistryTest extends TestCase
{
    use RefreshDatabase;

    public function test_registry_discovers_example_plugin(): void
    {
        $plugins = PluginRegistry::default()->discover();

        $this->assertArrayHasKey('example-audit', $plugins);
        $this->assertSame('Example Audit Plugin', $plugins['example-audit']['manifest']->name);
    }

    public function test_registry_only_returns_enabled_plugins(): void
    {
        InstalledPlugin::query()->create([
            'plugin_id' => 'example-audit',
            'name' => 'Example Audit Plugin',
            'version' => '1.0.0',
            'author' => 'Ranksmedia',
            'description' => 'Example',
            'path' => base_path('plugins/ExampleAudit'),
            'status' => 'disabled',
            'manifest' => [],
        ]);

        $this->assertSame([], PluginRegistry::default()->enabled());

        InstalledPlugin::query()->whereKey('example-audit')->update(['status' => 'enabled']);

        $this->assertArrayHasKey('example-audit', PluginRegistry::default()->enabled());
    }
}
