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
use LifeWheel\Plugins\AiReviews\ReviewContextBuilder;
use LifeWheel\Plugins\AiReviews\ReviewPeriod;
use Tests\TestCase;

require_once __DIR__.'/../../../plugins/AiReviews/src/ReviewContextBuilder.php';
require_once __DIR__.'/../../../plugins/AiReviews/src/ReviewPeriod.php';

final class AiReviewsPluginTest extends TestCase
{
    use RefreshDatabase;

    public function test_ai_reviews_manifest_is_valid(): void
    {
        $manifest = PluginManifest::fromArray(json_decode((string) file_get_contents(base_path('plugins/AiReviews/plugin.json')), true));

        $this->assertSame('ai-reviews', $manifest->id);
        $this->assertContains('ai.reviews', collect($manifest->features)->pluck('slug')->all());
        $this->assertContains('database/migrations', $manifest->migrations);
    }

    public function test_ai_reviews_requires_entitlement(): void
    {
        $this->loadAiReviewsPluginForTest();

        $this->actingAs(User::factory()->create())
            ->get('/app/ai-reviews')
            ->assertForbidden();
    }

    public function test_custom_period_rejects_reversed_dates(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ReviewPeriod::range('custom', '2026-08-31', '2026-08-01');
    }

    public function test_context_builder_scopes_records_to_authenticated_user(): void
    {
        $this->loadAiReviewsPluginForTest();
        $user = User::factory()->create();
        $other = User::factory()->create();
        $this->createLifeWheelAssessment($user, 8);
        $this->createLifeWheelAssessment($other, 2);

        $context = app(ReviewContextBuilder::class)->build($user, 'monthly', now()->startOfMonth(), now()->endOfMonth());

        $this->assertCount(1, $context['lifewheel']['assessments']);
        $this->assertSame(8.0, $context['lifewheel']['assessments'][0]['overall_score']);
    }

    public function test_entitled_user_can_generate_and_view_review(): void
    {
        $this->loadAiReviewsPluginForTest();
        $user = $this->entitledUser();
        $this->createLifeWheelAssessment($user, 7);

        $this->actingAs($user)
            ->post('/app/ai-reviews/reviews', ['period_type' => 'monthly'])
            ->assertRedirect();

        $reviewId = DB::table('ai_reviews')->where('user_id', $user->id)->value('id');

        $this->assertDatabaseHas('ai_usage_events', [
            'user_id' => $user->id,
            'feature_slug' => 'ai.reviews',
            'status' => 'succeeded',
        ]);

        $this->actingAs($user)
            ->get("/app/ai-reviews/reviews/{$reviewId}")
            ->assertOk()
            ->assertSee('Executive summary');
    }

    public function test_review_lookup_is_scoped_to_authenticated_user(): void
    {
        $this->loadAiReviewsPluginForTest();
        $user = $this->entitledUser();
        $other = $this->entitledUser();
        $reviewId = DB::table('ai_reviews')->insertGetId([
            'user_id' => $other->id,
            'period_type' => 'monthly',
            'period_start' => now()->startOfMonth()->toDateString(),
            'period_end' => now()->endOfMonth()->toDateString(),
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
            ->get("/app/ai-reviews/reviews/{$reviewId}")
            ->assertNotFound();
    }

    private function loadAiReviewsPluginForTest(): void
    {
        InstalledPlugin::query()->create([
            'plugin_id' => 'ai-reviews',
            'name' => 'AI Reviews',
            'version' => '1.0.0',
            'author' => 'Ranksmedia',
            'description' => 'Test AI Reviews plugin.',
            'path' => base_path('plugins/AiReviews'),
            'status' => 'enabled',
            'manifest' => json_decode((string) file_get_contents(base_path('plugins/AiReviews/plugin.json')), true),
            'installed_at' => now(),
            'activated_at' => now(),
        ]);

        $this->loadMigrationsFrom(base_path('plugins/LifeWheel/database/migrations'));
        $this->loadMigrationsFrom(base_path('plugins/AiReviews/database/migrations'));
        Route::middleware('web')->group(base_path('plugins/AiReviews/routes/web.php'));

        $provider = AiProvider::query()->firstOrCreate(['key' => 'mock'], ['name' => 'Mock', 'enabled' => true, 'mock_mode' => true]);
        AiModelRoute::query()->firstOrCreate(
            ['feature_slug' => 'ai.reviews', 'sort_order' => 10],
            ['ai_provider_id' => $provider->id, 'model' => 'mock-coach-v1', 'enabled' => true],
        );
    }

    private function entitledUser(): User
    {
        $user = User::factory()->create();
        $feature = Feature::query()->firstOrCreate(['slug' => 'ai.reviews'], ['name' => 'AI Reviews']);
        $package = Package::factory()->create();
        $package->features()->attach([$feature->id => ['enabled' => true]]);
        $user->packages()->attach($package->id, ['status' => 'active', 'starts_at' => now()]);

        return $user;
    }

    private function createLifeWheelAssessment(User $user, float $overall): void
    {
        $assessmentId = DB::table('lifewheel_assessments')->insertGetId([
            'user_id' => $user->id,
            'overall_score' => $overall,
            'reflection' => 'Private period reflection.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('lifewheel_scores')->insert([
            'assessment_id' => $assessmentId,
            'user_id' => $user->id,
            'area_key' => 'body',
            'area_name' => 'Body',
            'area_group' => 'Health',
            'score' => (int) $overall,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
