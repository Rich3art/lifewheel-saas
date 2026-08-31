<?php

namespace Tests\Feature\Plugins;

use App\Models\Feature;
use App\Models\InstalledPlugin;
use App\Models\Package;
use App\Models\User;
use App\Plugins\PluginManifest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class LessonsPluginTest extends TestCase
{
    use RefreshDatabase;

    public function test_lessons_manifest_is_valid(): void
    {
        $manifest = PluginManifest::fromArray(json_decode((string) file_get_contents(base_path('plugins/Lessons/plugin.json')), true));

        $this->assertSame('lessons', $manifest->id);
        $this->assertContains('lessons.use', collect($manifest->features)->pluck('slug')->all());
        $this->assertContains('database/migrations', $manifest->migrations);
    }

    public function test_lessons_requires_feature_entitlement(): void
    {
        $this->loadLessonsPluginForTest();

        $this->actingAs(User::factory()->create())
            ->get('/app/lessons')
            ->assertForbidden();
    }

    public function test_entitled_user_can_create_update_and_delete_lesson(): void
    {
        $this->loadLessonsPluginForTest();
        $user = $this->entitledUser(search: true);

        $this->actingAs($user)->post('/app/lessons/lessons', [
            'title' => 'Better mornings',
            'body' => 'Quiet starts improve my focus.',
            'areas' => ['mind', 'growth'],
            'learned_on' => '2026-08-31',
        ])->assertRedirect();

        $lessonId = DB::table('lessons')->where('user_id', $user->id)->value('id');

        $this->actingAs($user)->put("/app/lessons/lessons/{$lessonId}", [
            'title' => 'Better mornings updated',
            'body' => 'Quiet starts improve my focus and energy.',
            'areas' => ['mind'],
            'learned_on' => '2026-08-31',
        ])->assertRedirect();

        $this->assertDatabaseHas('lessons', [
            'id' => $lessonId,
            'user_id' => $user->id,
            'title' => 'Better mornings updated',
        ]);

        $this->actingAs($user)->delete("/app/lessons/lessons/{$lessonId}")->assertRedirect();
        $this->assertDatabaseMissing('lessons', ['id' => $lessonId]);
    }

    public function test_lesson_lookup_is_scoped_to_authenticated_user(): void
    {
        $this->loadLessonsPluginForTest();
        $user = $this->entitledUser();
        $other = $this->entitledUser();

        $lessonId = DB::table('lessons')->insertGetId([
            'user_id' => $other->id,
            'title' => 'Private lesson',
            'body' => 'Other user content',
            'source_type' => 'manual',
            'learned_on' => '2026-08-31',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user)
            ->get("/app/lessons/lessons/{$lessonId}")
            ->assertNotFound();
    }

    public function test_search_requires_search_entitlement(): void
    {
        $this->loadLessonsPluginForTest();
        $user = $this->entitledUser(search: false);

        $this->actingAs($user)
            ->get('/app/lessons?search=focus')
            ->assertForbidden();
    }

    public function test_idempotency_key_prevents_duplicate_lessons_for_same_user(): void
    {
        $this->loadLessonsPluginForTest();
        $user = $this->entitledUser();
        $payload = [
            'title' => 'Generated lesson',
            'body' => 'Same generated insight.',
            'source_type' => 'ai_review',
            'source_id' => 'review-1',
            'idempotency_key' => 'review-1-generated-lesson',
            'learned_on' => '2026-08-31',
        ];

        $this->actingAs($user)->post('/app/lessons/lessons', $payload)->assertRedirect();
        $this->actingAs($user)->post('/app/lessons/lessons', $payload)->assertSessionHasErrors();
    }

    private function loadLessonsPluginForTest(): void
    {
        InstalledPlugin::query()->create([
            'plugin_id' => 'lessons',
            'name' => 'Lessons',
            'version' => '1.0.0',
            'author' => 'Ranksmedia',
            'description' => 'Test Lessons plugin.',
            'path' => base_path('plugins/Lessons'),
            'status' => 'enabled',
            'manifest' => json_decode((string) file_get_contents(base_path('plugins/Lessons/plugin.json')), true),
            'installed_at' => now(),
            'activated_at' => now(),
        ]);

        $this->loadMigrationsFrom(base_path('plugins/Lessons/database/migrations'));
        Route::middleware('web')->group(base_path('plugins/Lessons/routes/web.php'));
    }

    private function entitledUser(bool $search = false): User
    {
        $user = User::factory()->create();
        $lessonFeature = Feature::query()->firstOrCreate(['slug' => 'lessons.use'], ['name' => 'Lessons']);
        $features = [$lessonFeature->id => ['enabled' => true]];

        if ($search) {
            $searchFeature = Feature::query()->firstOrCreate(['slug' => 'lessons.search'], ['name' => 'Lessons Search']);
            $features[$searchFeature->id] = ['enabled' => true];
        }

        $package = Package::factory()->create();
        $package->features()->attach($features);
        $user->packages()->attach($package->id, ['status' => 'active', 'starts_at' => now()]);

        return $user;
    }
}
