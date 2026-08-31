<?php

namespace Tests\Feature\Plugins;

use App\Models\AiModelRoute;
use App\Models\AiProvider;
use App\Models\Feature;
use App\Models\InstalledPlugin;
use App\Models\Package;
use App\Models\User;
use App\Plugins\PluginManifest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use LifeWheel\Plugins\AiCoach\CoachContextBuilder;
use Tests\TestCase;

require_once __DIR__.'/../../../plugins/AiCoach/src/CoachContextBuilder.php';

final class AiCoachPluginTest extends TestCase
{
    use RefreshDatabase;

    public function test_ai_coach_manifest_is_valid(): void
    {
        $manifest = PluginManifest::fromArray(json_decode((string) file_get_contents(base_path('plugins/AiCoach/plugin.json')), true));

        $this->assertSame('ai-coach', $manifest->id);
        $this->assertContains('ai.coach', collect($manifest->features)->pluck('slug')->all());
        $this->assertContains('database/migrations', $manifest->migrations);
    }

    public function test_ai_coach_requires_entitlement(): void
    {
        $this->loadAiCoachPluginForTest();

        $this->actingAs(User::factory()->create())
            ->get('/app/ai-coach')
            ->assertForbidden();
    }

    public function test_context_builder_scopes_lifewheel_records_to_authenticated_user(): void
    {
        $this->loadAiCoachPluginForTest();
        $user = User::factory()->create();
        $other = User::factory()->create();
        $this->createLifeWheelAssessment($user, 8, ['body' => 8]);
        $this->createLifeWheelAssessment($other, 2, ['body' => 2]);

        $context = app(CoachContextBuilder::class)->build($user, 'How is my body health changing?');

        $this->assertCount(1, $context['lifewheel']['recent_assessments']);
        $this->assertSame(8.0, $context['lifewheel']['recent_assessments'][0]['overall_score']);
    }

    public function test_entitled_user_can_create_conversation_and_ask_question(): void
    {
        $this->loadAiCoachPluginForTest();
        $user = $this->entitledUser();
        $this->createLifeWheelAssessment($user, 7, ['body' => 7, 'mind' => 6]);

        $this->actingAs($user)
            ->post('/app/ai-coach/conversations', ['question' => 'What should I focus on this week?'])
            ->assertRedirect();

        $conversationId = DB::table('ai_coach_conversations')->where('user_id', $user->id)->value('id');

        $this->actingAs($user)
            ->post("/app/ai-coach/conversations/{$conversationId}/messages", ['question' => 'What should I focus on this week?'])
            ->assertRedirect();

        $this->assertDatabaseHas('ai_coach_messages', [
            'conversation_id' => $conversationId,
            'user_id' => $user->id,
            'role' => 'assistant',
        ]);

        $this->assertDatabaseHas('ai_usage_events', [
            'user_id' => $user->id,
            'feature_slug' => 'ai.coach',
            'status' => 'succeeded',
        ]);
    }

    public function test_conversation_lookup_is_scoped_to_authenticated_user(): void
    {
        $this->loadAiCoachPluginForTest();
        $user = $this->entitledUser();
        $other = $this->entitledUser();
        $conversationId = DB::table('ai_coach_conversations')->insertGetId([
            'user_id' => $other->id,
            'title' => 'Private',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user)
            ->get("/app/ai-coach/conversations/{$conversationId}")
            ->assertNotFound();
    }

    private function loadAiCoachPluginForTest(): void
    {
        InstalledPlugin::query()->create([
            'plugin_id' => 'ai-coach',
            'name' => 'AI Coach',
            'version' => '1.0.0',
            'author' => 'Ranksmedia',
            'description' => 'Test AI Coach plugin.',
            'path' => base_path('plugins/AiCoach'),
            'status' => 'enabled',
            'manifest' => json_decode((string) file_get_contents(base_path('plugins/AiCoach/plugin.json')), true),
            'installed_at' => now(),
            'activated_at' => now(),
        ]);

        $this->loadMigrationsFrom(base_path('plugins/LifeWheel/database/migrations'));
        $this->loadMigrationsFrom(base_path('plugins/AiCoach/database/migrations'));
        Route::middleware('web')->group(base_path('plugins/AiCoach/routes/web.php'));

        $provider = AiProvider::query()->firstOrCreate(['key' => 'mock'], ['name' => 'Mock', 'enabled' => true, 'mock_mode' => true]);
        AiModelRoute::query()->firstOrCreate(
            ['feature_slug' => 'ai.coach', 'sort_order' => 10],
            ['ai_provider_id' => $provider->id, 'model' => 'mock-coach-v1', 'enabled' => true],
        );
    }

    private function entitledUser(): User
    {
        $user = User::factory()->create();
        $feature = Feature::query()->firstOrCreate(['slug' => 'ai.coach'], ['name' => 'AI Coach']);
        $package = Package::factory()->create();
        $package->features()->attach([$feature->id => ['enabled' => true]]);
        $user->packages()->attach($package->id, ['status' => 'active', 'starts_at' => now()]);

        return $user;
    }

    private function createLifeWheelAssessment(User $user, float $overall, array $scores): int
    {
        $assessmentId = DB::table('lifewheel_assessments')->insertGetId([
            'user_id' => $user->id,
            'overall_score' => $overall,
            'reflection' => 'A private reflection.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ($scores as $key => $score) {
            DB::table('lifewheel_scores')->insert([
                'assessment_id' => $assessmentId,
                'user_id' => $user->id,
                'area_key' => $key,
                'area_name' => ucfirst($key),
                'area_group' => 'Test',
                'score' => $score,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $assessmentId;
    }
}
