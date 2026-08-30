<?php

namespace Tests\Feature\Admin;

use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PluginAdminAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_without_plugin_permission_cannot_access_plugin_admin(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/admin/plugins')->assertForbidden();
    }

    public function test_admin_with_plugin_permission_can_access_plugin_admin(): void
    {
        $admin = User::factory()->create();

        foreach (['admin.dashboard.view', 'admin.plugins.manage'] as $slug) {
            $admin->directPermissions()->attach(Permission::factory()->create(['slug' => $slug]));
        }

        $this->actingAs($admin)->get('/admin/plugins')
            ->assertOk()
            ->assertSee('Plugins')
            ->assertSee('Example Audit Plugin');
    }
}
