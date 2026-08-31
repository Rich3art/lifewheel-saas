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

final class HabitsPluginTest extends TestCase
{
    use RefreshDatabase;

    public function test_habits_manifest_is_valid(): void
    {
        $manifest = PluginManifest::fromArray(json_decode((string) file_get_contents(base_path('plugins/Habits/plugin.json')), true));

        $this->assertSame('habits', $manifest->id);
        $this->assertContains('habits.use', collect($manifest->features)->pluck('slug')->all());
        $this->assertContains('database/migrations', $manifest->migrations);
    }

    public function test_habits_requires_feature_entitlement(): void
    {
        $this->loadHabitsPluginForTest();

        $this->actingAs(User::factory()->create())
            ->get('/app/habits')
            ->assertForbidden();
    }

    public function test_entitled_user_can_create_update_and_log_habit(): void
    {
        $this->loadHabitsPluginForTest();
        $user = $this->entitledUser();

        $this->actingAs($user)->post('/app/habits/habits', [
            'name' => 'Morning walk',
            'type' => 'positive',
            'areas' => ['body', 'mind'],
            'weekdays' => [1, 2, 3, 4, 5],
            'target_count' => 1,
            'status' => 'active',
            'notes' => 'Start the day moving.',
        ])->assertRedirect();

        $habitId = DB::table('habits')->where('user_id', $user->id)->value('id');

        $this->actingAs($user)->put("/app/habits/habits/{$habitId}", [
            'name' => 'Morning walk outside',
            'type' => 'positive',
            'areas' => ['body'],
            'weekdays' => [1, 3, 5],
            'target_count' => 1,
            'status' => 'active',
        ])->assertRedirect();

        $this->actingAs($user)->post("/app/habits/habits/{$habitId}/logs", [
            'logged_on' => '2026-08-31',
            'completed' => 1,
            'notes' => 'Done.',
        ])->assertRedirect();

        $this->actingAs($user)->post("/app/habits/habits/{$habitId}/logs", [
            'logged_on' => '2026-08-31',
            'completed' => 1,
            'notes' => 'Updated same day.',
        ])->assertRedirect();

        $this->assertDatabaseHas('habits', ['id' => $habitId, 'name' => 'Morning walk outside']);
        $this->assertDatabaseCount('habit_logs', 1);
        $this->assertDatabaseHas('habit_logs', ['habit_id' => $habitId, 'user_id' => $user->id, 'notes' => 'Updated same day.']);
    }

    public function test_habit_lookup_is_scoped_to_authenticated_user(): void
    {
        $this->loadHabitsPluginForTest();
        $user = $this->entitledUser();
        $other = $this->entitledUser();

        $habitId = DB::table('habits')->insertGetId([
            'user_id' => $other->id,
            'name' => 'Private habit',
            'type' => 'positive',
            'target_count' => 1,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user)
            ->get("/app/habits/habits/{$habitId}")
            ->assertNotFound();
    }

    private function loadHabitsPluginForTest(): void
    {
        InstalledPlugin::query()->create([
            'plugin_id' => 'habits',
            'name' => 'Habits',
            'version' => '1.0.0',
            'author' => 'Ranksmedia',
            'description' => 'Test Habits plugin.',
            'path' => base_path('plugins/Habits'),
            'status' => 'enabled',
            'manifest' => json_decode((string) file_get_contents(base_path('plugins/Habits/plugin.json')), true),
            'installed_at' => now(),
            'activated_at' => now(),
        ]);

        $this->loadMigrationsFrom(base_path('plugins/Habits/database/migrations'));
        Route::middleware('web')->group(base_path('plugins/Habits/routes/web.php'));
    }

    private function entitledUser(): User
    {
        $user = User::factory()->create();
        $habitFeature = Feature::query()->firstOrCreate(['slug' => 'habits.use'], ['name' => 'Habits']);
        $package = Package::factory()->create();
        $package->features()->attach([$habitFeature->id => ['enabled' => true]]);
        $user->packages()->attach($package->id, ['status' => 'active', 'starts_at' => now()]);

        return $user;
    }
}
