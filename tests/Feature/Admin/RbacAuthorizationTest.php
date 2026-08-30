<?php

namespace Tests\Feature\Admin;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class RbacAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_admin_dashboard(): void
    {
        $this->get('/admin/dashboard')->assertRedirect('/login');
    }

    public function test_member_without_admin_permission_is_forbidden(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/admin/dashboard')->assertForbidden();
    }

    public function test_user_with_admin_permission_can_access_admin_dashboard(): void
    {
        $user = User::factory()->create();
        $permission = Permission::factory()->create(['slug' => 'admin.dashboard.view']);
        $user->directPermissions()->attach($permission);

        $this->actingAs($user)->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('Super Admin dashboard');
    }

    public function test_role_permission_grants_admin_access(): void
    {
        $user = User::factory()->create();
        $role = Role::factory()->create();
        $permission = Permission::factory()->create(['slug' => 'admin.dashboard.view']);
        $role->permissions()->attach($permission);
        $user->roles()->attach($role);

        $this->actingAs($user)->get('/admin/dashboard')->assertOk();
    }

    public function test_user_management_requires_user_permission(): void
    {
        $user = User::factory()->create();
        $dashboard = Permission::factory()->create(['slug' => 'admin.dashboard.view']);
        $user->directPermissions()->attach($dashboard);

        $this->actingAs($user)->get('/admin/users')->assertForbidden();
    }
}
