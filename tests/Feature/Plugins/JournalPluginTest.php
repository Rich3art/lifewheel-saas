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

final class JournalPluginTest extends TestCase
{
    use RefreshDatabase;

    public function test_journal_manifest_is_valid(): void
    {
        $manifest = PluginManifest::fromArray(json_decode((string) file_get_contents(base_path('plugins/Journal/plugin.json')), true));

        $this->assertSame('journal', $manifest->id);
        $this->assertContains('journal.use', collect($manifest->features)->pluck('slug')->all());
        $this->assertContains('routes/web.php', $manifest->routes);
    }

    public function test_journal_requires_feature_entitlement(): void
    {
        $this->loadJournalPluginForTest();

        $this->actingAs(User::factory()->create())
            ->get('/app/journal')
            ->assertForbidden();
    }

    public function test_entitled_user_can_create_and_update_private_entry(): void
    {
        $this->loadJournalPluginForTest();
        $user = $this->entitledUser(search: true);

        $this->actingAs($user)->post('/app/journal/entries', [
            'title' => 'Strong morning',
            'body' => 'I noticed better focus after a quiet start.',
            'areas' => ['mind', 'growth'],
            'mood' => 8,
            'energy' => 7,
            'entry_date' => '2026-08-31',
        ])->assertRedirect();

        $entryId = DB::table('journal_entries')->where('user_id', $user->id)->value('id');

        $this->actingAs($user)->put("/app/journal/entries/{$entryId}", [
            'title' => 'Updated morning',
            'body' => 'A calmer start helped focus.',
            'areas' => ['mind'],
            'mood' => 7,
            'energy' => 7,
            'entry_date' => '2026-08-31',
        ])->assertRedirect();

        $this->assertDatabaseHas('journal_entries', [
            'id' => $entryId,
            'user_id' => $user->id,
            'title' => 'Updated morning',
        ]);
    }

    public function test_entry_lookup_is_scoped_to_authenticated_user(): void
    {
        $this->loadJournalPluginForTest();
        $user = $this->entitledUser();
        $other = $this->entitledUser();

        $entryId = DB::table('journal_entries')->insertGetId([
            'user_id' => $other->id,
            'title' => 'Private',
            'body' => 'Other user content',
            'entry_date' => '2026-08-31',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user)
            ->get("/app/journal/entries/{$entryId}")
            ->assertNotFound();
    }

    public function test_search_requires_search_entitlement(): void
    {
        $this->loadJournalPluginForTest();
        $user = $this->entitledUser(search: false);

        $this->actingAs($user)
            ->get('/app/journal?search=focus')
            ->assertForbidden();
    }

    private function loadJournalPluginForTest(): void
    {
        InstalledPlugin::query()->create([
            'plugin_id' => 'journal',
            'name' => 'Journal',
            'version' => '1.0.0',
            'author' => 'Ranksmedia',
            'description' => 'Test Journal plugin.',
            'path' => base_path('plugins/Journal'),
            'status' => 'enabled',
            'manifest' => json_decode((string) file_get_contents(base_path('plugins/Journal/plugin.json')), true),
            'installed_at' => now(),
            'activated_at' => now(),
        ]);

        $this->loadMigrationsFrom(base_path('plugins/Journal/database/migrations'));
        Route::middleware('web')->group(base_path('plugins/Journal/routes/web.php'));
    }

    private function entitledUser(bool $search = false): User
    {
        $user = User::factory()->create();
        $journal = Feature::query()->firstOrCreate(['slug' => 'journal.use'], ['name' => 'Journal']);
        $features = [$journal->id => ['enabled' => true]];

        if ($search) {
            $journalSearch = Feature::query()->firstOrCreate(['slug' => 'journal.search'], ['name' => 'Journal Search']);
            $features[$journalSearch->id] = ['enabled' => true];
        }

        $package = Package::factory()->create();
        $package->features()->attach($features);
        $user->packages()->attach($package->id, ['status' => 'active', 'starts_at' => now()]);

        return $user;
    }
}
