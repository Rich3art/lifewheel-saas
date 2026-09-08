<?php

namespace Tests\Feature\Security;

use App\Models\DataExport;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Plugins\PluginPackageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

final class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_protected_admin_without_two_factor_is_redirected_to_setup(): void
    {
        $user = User::factory()->create();
        $role = Role::factory()->create(['is_protected' => true]);
        $permission = Permission::factory()->create(['slug' => 'admin.dashboard.view']);
        $role->permissions()->attach($permission);
        $user->roles()->attach($role);

        $this->actingAs($user)
            ->get('/admin/dashboard')
            ->assertRedirect(route('two-factor.show', absolute: false));
    }

    public function test_member_export_download_rejects_paths_outside_private_export_directory(): void
    {
        $user = User::factory()->create();
        $path = storage_path('app/not-private-export.json');
        file_put_contents($path, '{"private":true}');

        $export = DataExport::query()->create([
            'user_id' => $user->id,
            'status' => 'ready',
            'format' => 'json',
            'path' => $path,
            'expires_at' => now()->addDay(),
        ]);

        $this->actingAs($user)
            ->get("/app/privacy-exports/{$export->id}")
            ->assertNotFound();
    }

    public function test_plugin_package_rejects_dot_segment_paths(): void
    {
        $zipPath = storage_path('app/test-unsafe-plugin.zip');
        $zip = new \ZipArchive();
        $zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $zip->addFromString('plugin.json', json_encode([
            'id' => 'unsafe-plugin',
            'name' => 'Unsafe',
            'version' => '1.0.0',
            'core_version' => '0.4.0',
            'php' => '8.2',
            'entry' => 'plugin.php',
        ]));
        $zip->addFromString('./plugin.php', '<?php');
        $zip->close();

        $file = new UploadedFile($zipPath, 'unsafe.zip', 'application/zip', null, true);

        $this->expectException(\RuntimeException::class);
        app(PluginPackageService::class)->upload($file);
    }
}
