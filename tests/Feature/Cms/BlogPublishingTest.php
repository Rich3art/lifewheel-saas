<?php

namespace Tests\Feature\Cms;

use App\Models\BlogPost;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class BlogPublishingTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_publish_blog_post_with_revision(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post('/admin/blog', [
            'title' => 'First Post',
            'slug' => 'first-post',
            'status' => 'published',
            'excerpt' => 'A short intro',
            'body' => 'Blog body',
        ])->assertRedirect();

        $post = BlogPost::query()->where('slug', 'first-post')->firstOrFail();
        $this->assertSame(1, $post->revisions()->count());
        $this->get('/blog/first-post')->assertOk()->assertSee('Blog body');
    }

    public function test_public_blog_hides_drafts(): void
    {
        BlogPost::factory()->create(['slug' => 'draft-post', 'status' => 'draft']);

        $this->get('/blog/draft-post')->assertNotFound();
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        foreach (['admin.dashboard.view', 'admin.blog.manage'] as $slug) {
            $admin->directPermissions()->attach(Permission::factory()->create(['slug' => $slug]));
        }

        return $admin;
    }
}
