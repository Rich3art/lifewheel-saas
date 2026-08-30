<?php

namespace Tests\Feature\Plugins;

use App\Models\InstalledPlugin;
use App\Plugins\PluginLifecycleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

final class PluginLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_lifecycle_installs_and_activates_example_plugin(): void
    {
        $service = app(PluginLifecycleService::class);

        $installed = $service->install('example-audit');
        $this->assertSame('disabled', $installed->status);
        $this->assertDatabaseHas('plugin_feature_registrations', ['plugin_id' => 'example-audit', 'slug' => 'example-audit.use']);
        $this->assertDatabaseHas('plugin_permission_registrations', ['plugin_id' => 'example-audit', 'slug' => 'example-audit.view']);

        $activated = $service->activate('example-audit');
        $this->assertSame('enabled', $activated->status);
    }

    public function test_lifecycle_prevents_deactivation_when_enabled_plugin_depends_on_it(): void
    {
        InstalledPlugin::query()->create([
            'plugin_id' => 'example-audit',
            'name' => 'Example Audit Plugin',
            'version' => '1.0.0',
            'author' => 'Ranksmedia',
            'description' => 'Example',
            'path' => base_path('plugins/ExampleAudit'),
            'status' => 'enabled',
            'manifest' => [],
        ]);

        InstalledPlugin::query()->create([
            'plugin_id' => 'dependent-plugin',
            'name' => 'Dependent Plugin',
            'version' => '1.0.0',
            'author' => 'Ranksmedia',
            'description' => 'Dependent',
            'path' => base_path('plugins/Dependent'),
            'status' => 'enabled',
            'manifest' => ['dependencies' => ['example-audit']],
        ]);

        $this->expectException(RuntimeException::class);

        app(PluginLifecycleService::class)->deactivate('example-audit');
    }
}
