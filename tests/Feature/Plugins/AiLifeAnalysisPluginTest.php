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
use LifeWheel\Plugins\AiLifeAnalysis\LifeContextBuilder;
use Tests\TestCase;

require_once __DIR__.'/../../../plugins/AiLifeAnalysis/src/LifeContextBuilder.php';

final class AiLifeAnalysisPluginTest extends TestCase
{
    use RefreshDatabase;

    public function test_ai_life_analysis_manifest_is_valid(): void
    {
        $manifest = PluginManifest::fromArray(json_decode((string) file_get_contents(base_path('plugins/AiLifeAnalysis/plugin.json')), true));

        $this->assertSame('ai-life-analysis', $manifest->id);
        $this->assertContains('ai.analysis', collect($manifest->features)->pluck('slug')->all());
        $this->assertContains('database/migrations', $manifest->migrations);
    }

    public function test_ai_life_analysis_requires_ai_analysis_entitlement(): void
    {
        $this->loadAiLifeAnalysisPluginForTest();

        $this->actingAs(User::factory()->create())
            ->get('/app/ai-life-analysis')
            ->assertForbidden();
    }

    public function test_generation_requires_existing_lifewheel_assessment(): void
    {
        $this->loadAiLifeAnalysisPluginForTest();
        $user = $this->entitledUser();

        $this->actingAs($user)
            ->post('/app/ai-life-analysis/analyses')
            ->assertStatus(422);
    }

    public function test_context_builder_uses_only_authenticated_user_lifewheel_history(): void
    {
        $this->loadAiLifeAnalysisPluginForTest();
        $user = User::factory()->create();
        $other = User::factory()->create();
        $this->createLifeWheelAssessment($user, 8, ['body' => 8, 'mind' => 6]);
        $this->createLifeWheelAssessment($other, 2, ['body' => 2, 'mind' => 2]);

        $context = app(LifeContextBuilder::class)->build($user);

        $this->assertCount(1, $context['lifewheel']['assessments']);
        $this->assertSame(8.0, $context['lifewheel']['assessments'][0]['overall_score']);
    }

    public function test_entitled_user_can_generate_and_view_analysis(): void
    {
        $this->loadAiLifeAnalysisPluginForTest();
        $user = $this->entitledUser();
        $this->createLifeWheelAssessment($user, 7, ['body' => 7, 'mind' => 6, 'soul' => 8]);

        $this->actingAs($user)
            ->post('/app/ai-life-analysis/analyses')
            ->assertRedirect();

        $analysisId = DB::table('ai_life_analyses')->where('user_id', $user->id)->value('id');

        $this->assertDatabaseHas('ai_usage_events', [
            'user_id' => $user->id,
            'feature_slug' => 'ai.analysis',
            'status' => 'succeeded',
        ]);

        $this->actingAs($user)
            ->get("/app/ai-life-analysis/analyses/{$analysisId}")
            ->assertOk()
            ->assertSee('Structured analysis');
    }

    public function test_analysis_lookup_is_scoped_to_authenticated_user(): void
    {
        $this->loadAiLifeAnalysisPluginForTest();
        $user = $this->entitledUser();
        $other = $this->entitledUser();
        $analysisId = DB::table('ai_life_analyses')->insertGetId([
            'user_id' => $other->id,
            'status' => 'completed',
            'context_summary' => json_encode([]),
            'content' => '{}',
            'structured' => json_encode([]),
            'provider_key' => 'mock',
            'model' => 'mock-coach-v1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user)
            ->get("/app/ai-life-analysis/analyses/{$analysisId}")
            ->assertNotFound();
    }

    private function loadAiLifeAnalysisPluginForTest(): void
    {
        InstalledPlugin::query()->create([
            'plugin_id' => 'ai-life-analysis',
            'name' => 'AI Life Analysis',
            'version' => '1.0.0',
            'author' => 'Ranksmedia',
            'description' => 'Test AI Life Analysis plugin.',
            'path' => base_path('plugins/AiLifeAnalysis'),
            'status' => 'enabled',
            'manifest' => json_decode((string) file_get_contents(base_path('plugins/AiLifeAnalysis/plugin.json')), true),
            'installed_at' => now(),
            'activated_at' => now(),
        ]);

        $this->loadMigrationsFrom(base_path('plugins/LifeWheel/database/migrations'));
        $this->loadMigrationsFrom(base_path('plugins/AiLifeAnalysis/database/migrations'));
        Route::middleware('web')->group(base_path('plugins/AiLifeAnalysis/routes/web.php'));

        $provider = AiProvider::query()->firstOrCreate(['key' => 'mock'], ['name' => 'Mock', 'enabled' => true, 'mock_mode' => true]);
        AiModelRoute::query()->firstOrCreate(
            ['feature_slug' => 'ai.analysis', 'sort_order' => 10],
            ['ai_provider_id' => $provider->id, 'model' => 'mock-coach-v1', 'enabled' => true],
        );
    }

    private function entitledUser(): User
    {
        $user = User::factory()->create();
        $feature = Feature::query()->firstOrCreate(['slug' => 'ai.analysis'], ['name' => 'AI Analysis']);
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
