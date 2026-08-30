<?php

namespace Tests\Unit;

use App\Plugins\PluginManifest;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class PluginManifestTest extends TestCase
{
    public function test_manifest_requires_core_fields(): void
    {
        $this->expectException(InvalidArgumentException::class);

        PluginManifest::fromArray(['id' => 'demo']);
    }

    public function test_manifest_rejects_entry_outside_src(): void
    {
        $this->expectException(InvalidArgumentException::class);

        PluginManifest::fromArray([
            'id' => 'demo',
            'name' => 'Demo',
            'version' => '1.0.0',
            'author' => 'LifeWheel',
            'description' => 'Demo',
            'core_version' => '^0.4.0',
            'php' => '^8.2',
            'entry' => '../demo.php',
        ]);
    }

    public function test_manifest_accepts_valid_plugin(): void
    {
        $manifest = PluginManifest::fromArray([
            'id' => 'demo',
            'name' => 'Demo',
            'version' => '1.0.0',
            'author' => 'LifeWheel',
            'description' => 'Demo',
            'core_version' => '^0.4.0',
            'php' => '^8.2',
            'entry' => 'src/DemoPlugin.php',
            'features' => [['slug' => 'demo.use']],
        ]);

        $this->assertSame('demo', $manifest->id);
        $this->assertSame([['slug' => 'demo.use']], $manifest->features);
    }
}
