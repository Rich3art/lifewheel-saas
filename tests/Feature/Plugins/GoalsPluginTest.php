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

final class GoalsPluginTest extends TestCase
{
    use RefreshDatabase;

    public function test_goals_manifest_is_valid(): void
    {
        $manifest = PluginManifest::fromArray(json_decode((string) file_get_contents(base_path('plugins/Goals/plugin.json')), true));

        $this->assertSame('goals', $manifest->id);
        $this->assertContains('goals.use', collect($manifest->features)->pluck('slug')->all());
        $this->assertContains('database/migrations', $manifest->migrations);
    }

    public function test_goals_requires_feature_entitlement(): void
    {
        $this->loadGoalsPluginForTest();

        $this->actingAs(User::factory()->create())
            ->get('/app/goals')
            ->assertForbidden();
    }

    public function test_entitled_user_can_create_goal_milestone_and_progress_record(): void
    {
        $this->loadGoalsPluginForTest();
        $user = $this->entitledUser(progress: true);

        $this->actingAs($user)->post('/app/goals/goals', [
            'name' => 'Build emergency fund',
            'why' => 'More resilience.',
            'areas' => ['money'],
            'status' => 'active',
            'success_criterion' => 'Save 5000.',
            'measure' => 'Savings',
            'baseline' => 1000,
            'current' => 1500,
            'target' => 5000,
            'unit' => 'USD',
            'due_date' => '2026-12-31',
        ])->assertRedirect();

        $goalId = DB::table('goals')->where('user_id', $user->id)->value('id');

        $this->actingAs($user)->post("/app/goals/goals/{$goalId}/milestones", [
            'name' => 'Reach 2500',
            'due_date' => '2026-10-31',
        ])->assertRedirect();

        $milestoneId = DB::table('goal_milestones')->where('goal_id', $goalId)->value('id');

        $this->actingAs($user)->put("/app/goals/goals/{$goalId}/milestones/{$milestoneId}/complete")->assertRedirect();
        $this->actingAs($user)->post("/app/goals/goals/{$goalId}/progress", [
            'value' => 2500,
            'recorded_on' => '2026-09-30',
            'notes' => 'Bonus moved this forward.',
        ])->assertRedirect();

        $this->assertDatabaseHas('goal_milestones', ['id' => $milestoneId, 'user_id' => $user->id]);
        $this->assertDatabaseHas('goal_progress_records', ['goal_id' => $goalId, 'user_id' => $user->id, 'value' => 2500]);
        $this->assertDatabaseHas('goals', ['id' => $goalId, 'current' => 2500]);
    }

    public function test_goal_lookup_is_scoped_to_authenticated_user(): void
    {
        $this->loadGoalsPluginForTest();
        $user = $this->entitledUser();
        $other = $this->entitledUser();

        $goalId = DB::table('goals')->insertGetId([
            'user_id' => $other->id,
            'name' => 'Private goal',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user)
            ->get("/app/goals/goals/{$goalId}")
            ->assertNotFound();
    }

    public function test_progress_requires_progress_entitlement(): void
    {
        $this->loadGoalsPluginForTest();
        $user = $this->entitledUser(progress: false);
        $goalId = DB::table('goals')->insertGetId([
            'user_id' => $user->id,
            'name' => 'Private goal',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user)->post("/app/goals/goals/{$goalId}/progress", [
            'value' => 10,
            'recorded_on' => '2026-08-31',
        ])->assertForbidden();
    }

    private function loadGoalsPluginForTest(): void
    {
        InstalledPlugin::query()->create([
            'plugin_id' => 'goals',
            'name' => 'Goals',
            'version' => '1.0.0',
            'author' => 'Ranksmedia',
            'description' => 'Test Goals plugin.',
            'path' => base_path('plugins/Goals'),
            'status' => 'enabled',
            'manifest' => json_decode((string) file_get_contents(base_path('plugins/Goals/plugin.json')), true),
            'installed_at' => now(),
            'activated_at' => now(),
        ]);

        $this->loadMigrationsFrom(base_path('plugins/Goals/database/migrations'));
        Route::middleware('web')->group(base_path('plugins/Goals/routes/web.php'));
    }

    private function entitledUser(bool $progress = false): User
    {
        $user = User::factory()->create();
        $goalFeature = Feature::query()->firstOrCreate(['slug' => 'goals.use'], ['name' => 'Goals']);
        $features = [$goalFeature->id => ['enabled' => true]];

        if ($progress) {
            $progressFeature = Feature::query()->firstOrCreate(['slug' => 'goals.progress'], ['name' => 'Goal Progress Tracking']);
            $features[$progressFeature->id] = ['enabled' => true];
        }

        $package = Package::factory()->create();
        $package->features()->attach($features);
        $user->packages()->attach($package->id, ['status' => 'active', 'starts_at' => now()]);

        return $user;
    }
}
