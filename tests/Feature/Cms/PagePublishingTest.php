<?php

namespace Tests\Feature\Cms;

use App\Models\Page;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PagePublishingTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_page_save_creates_version(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->post('/admin/pages', [
            'title' => 'Privacy Policy',
            'slug' => 'privacy-policy',
            'status' => 'published',
            'body' => 'Version one',
            'is_legal' => 1,
        ]);

        $response->assertRedirect();
        $page = Page::query()->where('slug', 'privacy-policy')->firstOrFail();
        $this->assertSame(1, $page->versions()->count());
        $this->assertNotNull($page->current_version_id);
    }

    public function test_public_page_hides_drafts(): void
    {
        Page::factory()->create(['slug' => 'draft-page', 'status' => 'draft']);

        $this->get('/draft-page')->assertNotFound();
    }

    public function test_public_page_shows_published_version(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post('/admin/pages', [
            'title' => 'About',
            'slug' => 'about',
            'status' => 'published',
            'body' => 'About content',
        ]);

        $this->get('/about')->assertOk()->assertSee('About content');
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        foreach (['admin.dashboard.view', 'admin.pages.manage'] as $slug) {
            $admin->directPermissions()->attach(Permission::factory()->create(['slug' => $slug]));
        }

        return $admin;
    }
}
