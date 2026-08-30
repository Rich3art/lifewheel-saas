<?php

namespace Tests\Feature\Admin;

use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CmsAdminAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_cannot_access_pages_admin(): void
    {
        $this->actingAs(User::factory()->create())->get('/admin/pages')->assertForbidden();
    }

    public function test_member_cannot_access_blog_admin(): void
    {
        $this->actingAs(User::factory()->create())->get('/admin/blog')->assertForbidden();
    }

    public function test_admin_with_page_permission_can_access_pages(): void
    {
        $admin = $this->adminWith('admin.pages.manage');

        $this->actingAs($admin)->get('/admin/pages')->assertOk()->assertSee('Pages');
    }

    public function test_admin_with_blog_permission_can_access_blog(): void
    {
        $admin = $this->adminWith('admin.blog.manage');

        $this->actingAs($admin)->get('/admin/blog')->assertOk()->assertSee('Blog');
    }

    private function adminWith(string $permission): User
    {
        $admin = User::factory()->create();

        foreach (['admin.dashboard.view', $permission] as $slug) {
            $admin->directPermissions()->attach(Permission::factory()->create(['slug' => $slug]));
        }

        return $admin;
    }
}
