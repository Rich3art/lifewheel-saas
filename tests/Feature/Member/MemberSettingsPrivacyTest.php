<?php

namespace Tests\Feature\Member;

use App\Models\DataExport;
use App\Models\MemberSettingsSection;
use App\Models\Page;
use App\Models\PolicyAcceptance;
use App\Models\PrivacyConsent;
use App\Models\PrivacyRequest;
use App\Models\User;
use App\Services\Cms\PagePublisher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class MemberSettingsPrivacyTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_open_enabled_settings_sections(): void
    {
        $user = User::factory()->create();
        MemberSettingsSection::query()->create(['key' => 'profile', 'label' => 'Profile', 'enabled' => true, 'sort_order' => 10]);
        MemberSettingsSection::query()->create(['key' => 'ai', 'label' => 'AI preferences', 'enabled' => false, 'sort_order' => 20]);

        $this->actingAs($user)->get('/app/settings')
            ->assertOk()
            ->assertSee('Profile')
            ->assertDontSee('AI preferences');
    }

    public function test_member_can_create_data_export_request(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/app/privacy-requests', [
            'type' => 'data_export',
            'details' => 'I need a portable copy.',
        ])->assertRedirect();

        $this->assertDatabaseHas('privacy_requests', [
            'user_id' => $user->id,
            'type' => 'data_export',
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('data_exports', [
            'user_id' => $user->id,
            'status' => 'ready',
            'format' => 'json',
        ]);
    }

    public function test_member_settings_only_show_own_privacy_requests(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        MemberSettingsSection::query()->create(['key' => 'privacy', 'label' => 'Privacy', 'enabled' => true, 'sort_order' => 10]);
        PrivacyRequest::query()->create(['user_id' => $user->id, 'type' => 'correction', 'details' => 'Mine']);
        PrivacyRequest::query()->create(['user_id' => $other->id, 'type' => 'erasure', 'details' => 'Other user private request']);

        $this->actingAs($user)->get('/app/settings')
            ->assertOk()
            ->assertSee('Mine')
            ->assertDontSee('Other user private request');
    }

    public function test_member_can_download_only_own_ready_export(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $path = storage_path('app/private/privacy_exports/test-export.json');
        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0750, true);
        }
        file_put_contents($path, '{"ok":true}');

        $ownExport = DataExport::query()->create([
            'user_id' => $user->id,
            'status' => 'ready',
            'format' => 'json',
            'path' => $path,
            'expires_at' => now()->addDay(),
        ]);
        $otherExport = DataExport::query()->create([
            'user_id' => $other->id,
            'status' => 'ready',
            'format' => 'json',
            'path' => $path,
            'expires_at' => now()->addDay(),
        ]);

        $this->actingAs($user)->get("/app/privacy-exports/{$ownExport->id}")->assertOk();
        $this->actingAs($user)->get("/app/privacy-exports/{$otherExport->id}")->assertNotFound();
    }

    public function test_member_can_update_privacy_consents(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put('/app/privacy-consents', [
            'consents' => ['product_updates' => 1],
        ])->assertRedirect();

        $this->assertDatabaseHas('privacy_consents', [
            'user_id' => $user->id,
            'key' => 'product_updates',
            'granted' => true,
        ]);
        $this->assertDatabaseHas('privacy_consents', [
            'user_id' => $user->id,
            'key' => 'research_feedback',
            'granted' => false,
        ]);
    }

    public function test_member_can_accept_current_legal_policy_version(): void
    {
        $user = User::factory()->create();
        $page = Page::query()->create([
            'title' => 'Privacy Policy',
            'slug' => 'privacy-policy',
            'status' => 'published',
            'body' => 'Policy',
            'is_legal' => true,
            'published_at' => now(),
        ]);
        app(PagePublisher::class)->save($page, $page->only([
            'title', 'slug', 'status', 'body', 'seo_title', 'meta_description', 'canonical_url', 'open_graph', 'is_legal', 'published_at',
        ]), $user);

        $this->actingAs($user)->post("/app/policies/{$page->id}/accept")->assertRedirect();

        $this->assertSame(1, PolicyAcceptance::query()->where('user_id', $user->id)->count());
    }
}
