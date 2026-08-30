<?php

namespace Tests\Feature\Admin;

use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class RolePermissionManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_role_with_permissions(): void
    {
        $admin = $this->adminWith('admin.roles.manage');
        $permission = Permission::factory()->create();

        $this->actingAs($admin)->post('/admin/roles', [
            'name' => 'Support',
            'slug' => 'support',
            'description' => 'Support staff',
            'permissions' => [$permission->id],
        ])->assertRedirect();

        $this->assertDatabaseHas('roles', ['slug' => 'support']);
    }

    public function test_admin_can_create_permission(): void
    {
        $admin = $this->adminWith('admin.permissions.manage');

        $this->actingAs($admin)->post('/admin/permissions', [
            'name' => 'Manage Billing',
            'slug' => 'admin.billing.manage',
        ])->assertRedirect();

        $this->assertDatabaseHas('permissions', ['slug' => 'admin.billing.manage']);
    }

    private function adminWith(string $permissionSlug): User
    {
        $admin = User::factory()->create();

        foreach (['admin.dashboard.view', $permissionSlug] as $slug) {
            $admin->directPermissions()->attach(Permission::factory()->create(['slug' => $slug]));
        }

        return $admin;
    }
}
