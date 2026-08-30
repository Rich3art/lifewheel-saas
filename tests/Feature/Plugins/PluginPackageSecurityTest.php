<?php

namespace Tests\Feature\Plugins;

use App\Plugins\PluginPackageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use RuntimeException;
use Tests\TestCase;
use ZipArchive;

final class PluginPackageSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_upload_rejects_zip_without_root_manifest(): void
    {
        $zip = $this->zipWith(['readme.md' => 'No manifest']);

        $this->expectException(RuntimeException::class);

        app(PluginPackageService::class)->upload($zip);
    }

    public function test_upload_rejects_zip_slip_paths(): void
    {
        $zip = $this->zipWith([
            'plugin.json' => json_encode($this->manifest()),
            '../evil.php' => '<?php echo "bad";',
        ]);

        $this->expectException(RuntimeException::class);

        app(PluginPackageService::class)->upload($zip);
    }

    public function test_upload_rejects_disallowed_file_extensions(): void
    {
        $zip = $this->zipWith([
            'plugin.json' => json_encode($this->manifest()),
            'src/demo.exe' => 'bad',
        ]);

        $this->expectException(RuntimeException::class);

        app(PluginPackageService::class)->upload($zip);
    }

    private function zipWith(array $files): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'plugin').'.zip';
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::CREATE);

        foreach ($files as $name => $contents) {
            $zip->addFromString($name, $contents);
        }

        $zip->close();

        return new UploadedFile($path, 'plugin.zip', 'application/zip', null, true);
    }

    private function manifest(): array
    {
        return [
            'id' => 'secure-demo',
            'name' => 'Secure Demo',
            'version' => '1.0.0',
            'author' => 'LifeWheel',
            'description' => 'Demo',
            'core_version' => '^0.4.0',
            'php' => '^8.2',
            'entry' => 'src/DemoPlugin.php',
            'class' => 'DemoPlugin',
        ];
    }
}
