<?php

namespace Tests\Feature\Admin;

use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SaasAdminAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_without_saas_permission_cannot_access_packages(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/admin/packages')->assertForbidden();
    }

    public function test_admin_with_saas_permission_can_access_packages(): void
    {
        $admin = User::factory()->create();

        foreach (['admin.dashboard.view', 'admin.saas.manage'] as $slug) {
            $admin->directPermissions()->attach(Permission::factory()->create(['slug' => $slug]));
        }

        $this->actingAs($admin)->get('/admin/packages')
            ->assertOk()
            ->assertSee('Packages');
    }
}
