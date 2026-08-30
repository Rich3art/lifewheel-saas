<?php

namespace Tests\Feature\Admin;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class UserAdministrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_assign_roles_to_a_user(): void
    {
        [$admin, $target] = $this->adminAndTarget();
        $role = Role::factory()->create();

        $this->actingAs($admin)->put("/admin/users/{$target->id}/roles", [
            'roles' => [$role->id],
        ])->assertRedirect();

        $this->assertTrue($target->fresh()->roles()->whereKey($role->id)->exists());
    }

    public function test_admin_can_assign_direct_permissions_to_a_user(): void
    {
        [$admin, $target] = $this->adminAndTarget();
        $permission = Permission::factory()->create();

        $this->actingAs($admin)->put("/admin/users/{$target->id}/permissions", [
            'permissions' => [$permission->id],
        ])->assertRedirect();

        $this->assertTrue($target->fresh()->directPermissions()->whereKey($permission->id)->exists());
    }

    public function test_admin_cannot_suspend_self(): void
    {
        [$admin] = $this->adminAndTarget();

        $this->actingAs($admin)->put("/admin/users/{$admin->id}/suspend")->assertStatus(422);
    }

    public function test_user_manager_without_role_permission_cannot_assign_protected_role(): void
    {
        [$admin, $target] = $this->adminAndTarget();
        $role = Role::factory()->create(['is_protected' => true]);

        $this->actingAs($admin)->put("/admin/users/{$target->id}/roles", [
            'roles' => [$role->id],
        ])->assertForbidden();
    }

    private function adminAndTarget(): array
    {
        $admin = User::factory()->create();
        $target = User::factory()->create();

        foreach (['admin.dashboard.view', 'admin.users.manage'] as $slug) {
            $admin->directPermissions()->attach(Permission::factory()->create(['slug' => $slug]));
        }

        return [$admin, $target];
    }
}
