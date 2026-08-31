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

final class ProjectsPluginTest extends TestCase
{
    use RefreshDatabase;

    public function test_projects_manifest_is_valid(): void
    {
        $manifest = PluginManifest::fromArray(json_decode((string) file_get_contents(base_path('plugins/Projects/plugin.json')), true));

        $this->assertSame('projects', $manifest->id);
        $this->assertContains('projects.use', collect($manifest->features)->pluck('slug')->all());
        $this->assertContains('database/migrations', $manifest->migrations);
    }

    public function test_projects_requires_feature_entitlement(): void
    {
        $this->loadProjectsPluginForTest();

        $this->actingAs(User::factory()->create())
            ->get('/app/projects')
            ->assertForbidden();
    }

    public function test_entitled_user_can_create_project_and_complete_task(): void
    {
        $this->loadProjectsPluginForTest();
        $user = $this->entitledUser(tasks: true);

        $this->actingAs($user)->post('/app/projects/projects', [
            'name' => 'Launch member onboarding',
            'description' => 'Ship the first member onboarding workflow.',
            'areas' => ['mission', 'growth'],
            'status' => 'active',
            'priority' => 'high',
            'start_date' => '2026-08-31',
            'due_date' => '2026-09-30',
        ])->assertRedirect();

        $projectId = DB::table('projects')->where('user_id', $user->id)->value('id');

        $this->actingAs($user)->post("/app/projects/projects/{$projectId}/tasks", [
            'title' => 'Draft onboarding screens',
            'due_date' => '2026-09-07',
            'notes' => 'Keep it light.',
        ])->assertRedirect();

        $taskId = DB::table('project_tasks')->where('project_id', $projectId)->value('id');

        $this->actingAs($user)->put("/app/projects/projects/{$projectId}/tasks/{$taskId}/complete")->assertRedirect();

        $this->assertDatabaseHas('projects', ['id' => $projectId, 'user_id' => $user->id]);
        $this->assertDatabaseHas('project_tasks', ['id' => $taskId, 'status' => 'completed']);
    }

    public function test_project_lookup_is_scoped_to_authenticated_user(): void
    {
        $this->loadProjectsPluginForTest();
        $user = $this->entitledUser();
        $other = $this->entitledUser();

        $projectId = DB::table('projects')->insertGetId([
            'user_id' => $other->id,
            'name' => 'Private project',
            'status' => 'active',
            'priority' => 'medium',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user)
            ->get("/app/projects/projects/{$projectId}")
            ->assertNotFound();
    }

    public function test_task_creation_requires_task_entitlement(): void
    {
        $this->loadProjectsPluginForTest();
        $user = $this->entitledUser(tasks: false);
        $projectId = DB::table('projects')->insertGetId([
            'user_id' => $user->id,
            'name' => 'Private project',
            'status' => 'active',
            'priority' => 'medium',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user)->post("/app/projects/projects/{$projectId}/tasks", [
            'title' => 'Blocked task',
        ])->assertForbidden();
    }

    private function loadProjectsPluginForTest(): void
    {
        InstalledPlugin::query()->create([
            'plugin_id' => 'projects',
            'name' => 'Projects',
            'version' => '1.0.0',
            'author' => 'Ranksmedia',
            'description' => 'Test Projects plugin.',
            'path' => base_path('plugins/Projects'),
            'status' => 'enabled',
            'manifest' => json_decode((string) file_get_contents(base_path('plugins/Projects/plugin.json')), true),
            'installed_at' => now(),
            'activated_at' => now(),
        ]);

        $this->loadMigrationsFrom(base_path('plugins/Projects/database/migrations'));
        Route::middleware('web')->group(base_path('plugins/Projects/routes/web.php'));
    }

    private function entitledUser(bool $tasks = false): User
    {
        $user = User::factory()->create();
        $projectFeature = Feature::query()->firstOrCreate(['slug' => 'projects.use'], ['name' => 'Projects']);
        $features = [$projectFeature->id => ['enabled' => true]];

        if ($tasks) {
            $taskFeature = Feature::query()->firstOrCreate(['slug' => 'projects.tasks'], ['name' => 'Project Tasks']);
            $features[$taskFeature->id] = ['enabled' => true];
        }

        $package = Package::factory()->create();
        $package->features()->attach($features);
        $user->packages()->attach($package->id, ['status' => 'active', 'starts_at' => now()]);

        return $user;
    }
}
