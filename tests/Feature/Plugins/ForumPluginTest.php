<?php

namespace Tests\Feature\Plugins;

use App\Models\Feature;
use App\Models\InstalledPlugin;
use App\Models\Package;
use App\Models\Permission;
use App\Models\User;
use App\Plugins\PluginManifest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class ForumPluginTest extends TestCase
{
    use RefreshDatabase;

    public function test_forum_manifest_is_valid(): void
    {
        $manifest = PluginManifest::fromArray(json_decode((string) file_get_contents(base_path('plugins/Forum/plugin.json')), true));

        $this->assertSame('forum', $manifest->id);
        $this->assertContains('forum.use', collect($manifest->features)->pluck('slug')->all());
        $this->assertContains('forum.moderate', collect($manifest->permissions)->pluck('slug')->all());
    }

    public function test_forum_requires_entitlement(): void
    {
        $this->loadForumPluginForTest();

        $this->actingAs(User::factory()->create())
            ->get('/app/community')
            ->assertForbidden();
    }

    public function test_entitled_user_can_create_topic_and_reply(): void
    {
        $this->loadForumPluginForTest();
        $user = $this->entitledUser(['forum.use', 'forum.create_topic', 'forum.reply']);
        $categoryId = $this->category();

        $this->actingAs($user)
            ->post('/app/community/topics', [
                'category_id' => $categoryId,
                'title' => 'How do you run a better weekly review?',
                'body' => 'I am trying to keep my weekly reflection more consistent.',
            ])
            ->assertRedirect();

        $topicId = DB::table('forum_topics')->where('user_id', $user->id)->value('id');

        $this->actingAs($user)
            ->post("/app/community/topics/{$topicId}/replies", ['body' => 'A practical reply.'])
            ->assertRedirect();

        $this->assertDatabaseHas('forum_replies', [
            'topic_id' => $topicId,
            'user_id' => $user->id,
            'status' => 'visible',
        ]);
    }

    public function test_member_without_reply_feature_cannot_reply_by_direct_request(): void
    {
        $this->loadForumPluginForTest();
        $owner = $this->entitledUser(['forum.use', 'forum.create_topic']);
        $user = $this->entitledUser(['forum.use']);
        $topicId = $this->topic($owner, $this->category());

        $this->actingAs($user)
            ->post("/app/community/topics/{$topicId}/replies", ['body' => 'Trying to reply'])
            ->assertForbidden();
    }

    public function test_conversation_lookup_requires_participant_membership(): void
    {
        $this->loadForumPluginForTest();
        $owner = $this->entitledUser(['forum.message']);
        $intruder = $this->entitledUser(['forum.message']);
        $conversationId = DB::table('social_conversations')->insertGetId(['created_at' => now(), 'updated_at' => now()]);
        DB::table('social_conversation_participants')->insert([
            'conversation_id' => $conversationId,
            'user_id' => $owner->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($intruder)
            ->get("/app/messages/conversations/{$conversationId}")
            ->assertNotFound();
    }

    public function test_blocked_member_cannot_start_message(): void
    {
        $this->loadForumPluginForTest();
        $sender = $this->entitledUser(['forum.message']);
        $recipient = $this->entitledUser(['forum.message']);
        DB::table('social_blocks')->insert([
            'blocker_id' => $recipient->id,
            'blocked_id' => $sender->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($sender)
            ->post('/app/messages/conversations', [
                'recipient_id' => $recipient->id,
                'body' => 'Hello',
            ])
            ->assertForbidden();
    }

    public function test_private_message_report_requires_conversation_participation(): void
    {
        $this->loadForumPluginForTest();
        $owner = $this->entitledUser(['forum.use']);
        $intruder = $this->entitledUser(['forum.use']);
        $conversationId = DB::table('social_conversations')->insertGetId(['created_at' => now(), 'updated_at' => now()]);
        DB::table('social_conversation_participants')->insert([
            'conversation_id' => $conversationId,
            'user_id' => $owner->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $messageId = DB::table('social_messages')->insertGetId([
            'conversation_id' => $conversationId,
            'sender_id' => $owner->id,
            'body' => 'Private',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($intruder)
            ->post('/app/community/reports', [
                'reportable_type' => 'message',
                'reportable_id' => $messageId,
                'reason' => 'Cannot see this',
            ])
            ->assertNotFound();
    }

    public function test_moderation_reports_require_permission(): void
    {
        $this->loadForumPluginForTest();

        $this->actingAs(User::factory()->create())
            ->get('/admin/forum/reports')
            ->assertForbidden();
    }

    public function test_moderator_can_update_report_status(): void
    {
        $this->loadForumPluginForTest();
        $moderator = User::factory()->create();
        $permission = Permission::query()->firstOrCreate(['slug' => 'forum.moderate'], ['name' => 'Moderate forum']);
        $moderator->directPermissions()->attach($permission->id);
        $reporter = $this->entitledUser(['forum.use']);
        $reportId = DB::table('social_reports')->insertGetId([
            'reporter_id' => $reporter->id,
            'reportable_type' => 'topic',
            'reportable_id' => 1,
            'reason' => 'Spam',
            'status' => 'open',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($moderator)
            ->put("/admin/forum/reports/{$reportId}", ['status' => 'resolved'])
            ->assertRedirect();

        $this->assertDatabaseHas('social_reports', [
            'id' => $reportId,
            'status' => 'resolved',
            'reviewed_by' => $moderator->id,
        ]);
    }

    private function loadForumPluginForTest(): void
    {
        InstalledPlugin::query()->create([
            'plugin_id' => 'forum',
            'name' => 'Forum Social Messaging',
            'version' => '1.0.0',
            'author' => 'Ranksmedia',
            'description' => 'Test Forum plugin.',
            'path' => base_path('plugins/Forum'),
            'status' => 'enabled',
            'manifest' => json_decode((string) file_get_contents(base_path('plugins/Forum/plugin.json')), true),
            'installed_at' => now(),
            'activated_at' => now(),
        ]);

        $this->loadMigrationsFrom(base_path('plugins/Forum/database/migrations'));
        Route::middleware('web')->group(base_path('plugins/Forum/routes/web.php'));
    }

    private function entitledUser(array $features): User
    {
        $user = User::factory()->create();
        $package = Package::factory()->create();

        foreach ($features as $slug) {
            $feature = Feature::query()->firstOrCreate(['slug' => $slug], ['name' => $slug]);
            $package->features()->attach([$feature->id => ['enabled' => true]]);
        }

        $user->packages()->attach($package->id, ['status' => 'active', 'starts_at' => now()]);

        return $user;
    }

    private function category(): int
    {
        return DB::table('forum_categories')->insertGetId([
            'name' => 'General',
            'slug' => 'general',
            'description' => 'General discussion.',
            'is_active' => true,
            'sort_order' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function topic(User $user, int $categoryId): int
    {
        return DB::table('forum_topics')->insertGetId([
            'category_id' => $categoryId,
            'user_id' => $user->id,
            'title' => 'Test topic',
            'body' => 'This is a test topic.',
            'status' => 'open',
            'last_activity_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
