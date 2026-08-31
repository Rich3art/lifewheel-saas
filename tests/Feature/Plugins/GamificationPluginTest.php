<?php

namespace Tests\Feature\Plugins;

use App\Models\InstalledPlugin;
use App\Models\Permission;
use App\Models\User;
use App\Plugins\PluginManifest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use LifeWheel\Plugins\Gamification\Listeners\AwardLifeWheelAssessmentXp;
use LifeWheel\Plugins\Gamification\XpLedger;
use Tests\TestCase;

require_once __DIR__.'/../../../plugins/Gamification/src/XpLedger.php';
require_once __DIR__.'/../../../plugins/Gamification/src/Listeners/AwardLifeWheelAssessmentXp.php';

final class GamificationPluginTest extends TestCase
{
    use RefreshDatabase;

    public function test_gamification_manifest_is_valid(): void
    {
        $manifest = PluginManifest::fromArray(json_decode((string) file_get_contents(base_path('plugins/Gamification/plugin.json')), true));

        $this->assertSame('gamification', $manifest->id);
        $this->assertContains('gamification.use', collect($manifest->features)->pluck('slug')->all());
        $this->assertContains('database/migrations', $manifest->migrations);
    }

    public function test_xp_ledger_awards_idempotently(): void
    {
        $this->loadGamificationPluginForTest();
        $user = User::factory()->create();
        $ledger = app(XpLedger::class);

        $this->assertTrue($ledger->award($user->id, 'test.event', 'test', '1', 10));
        $this->assertFalse($ledger->award($user->id, 'test.event', 'test', '1', 10));
        $this->assertSame(10, $ledger->totalFor($user->id));
        $this->assertDatabaseCount('xp_events', 1);
    }

    public function test_member_xp_page_requires_gamification_entitlement(): void
    {
        $this->loadGamificationPluginForTest();

        $this->actingAs(User::factory()->create())
            ->get('/app/xp')
            ->assertForbidden();
    }

    public function test_lifewheel_assessment_event_awards_xp_once(): void
    {
        $this->loadGamificationPluginForTest();
        $user = User::factory()->create();

        DB::table('gamification_rules')->insert([
            'event_type' => 'lifewheel.assessment_completed',
            'label' => 'Complete LifeWheel assessment',
            'xp' => 25,
            'enabled' => true,
            'cooldown_hours' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $event = (object) [
            'user' => $user,
            'assessmentId' => 10,
            'overallScore' => 7.5,
        ];

        $listener = app(AwardLifeWheelAssessmentXp::class);
        $listener->handle($event);
        $listener->handle($event);

        $this->assertDatabaseCount('xp_events', 1);
        $this->assertDatabaseHas('xp_events', [
            'user_id' => $user->id,
            'event_type' => 'lifewheel.assessment_completed',
            'source_id' => '10',
            'xp' => 25,
        ]);
    }

    public function test_admin_can_update_gamification_rule(): void
    {
        $this->loadGamificationPluginForTest();
        $admin = User::factory()->create();
        $admin->directPermissions()->attach(Permission::factory()->create(['slug' => 'admin.saas.manage']));
        $ruleId = DB::table('gamification_rules')->insertGetId([
            'event_type' => 'lifewheel.assessment_completed',
            'label' => 'Complete LifeWheel assessment',
            'xp' => 25,
            'enabled' => true,
            'cooldown_hours' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin)->put("/admin/gamification/rules/{$ruleId}", [
            'xp' => 50,
            'enabled' => 1,
            'cooldown_hours' => 24,
        ])->assertRedirect();

        $this->assertDatabaseHas('gamification_rules', ['id' => $ruleId, 'xp' => 50, 'cooldown_hours' => 24]);
    }

    private function loadGamificationPluginForTest(): void
    {
        InstalledPlugin::query()->create([
            'plugin_id' => 'gamification',
            'name' => 'Gamification',
            'version' => '1.0.0',
            'author' => 'Ranksmedia',
            'description' => 'Test Gamification plugin.',
            'path' => base_path('plugins/Gamification'),
            'status' => 'enabled',
            'manifest' => json_decode((string) file_get_contents(base_path('plugins/Gamification/plugin.json')), true),
            'installed_at' => now(),
            'activated_at' => now(),
        ]);

        $this->loadMigrationsFrom(base_path('plugins/Gamification/database/migrations'));
        Route::middleware('web')->group(base_path('plugins/Gamification/routes/web.php'));
    }

}
