<?php

namespace Tests\Feature\Install;

use App\Models\User;
use App\Services\Installer\InstallationState;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class InstallerAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        $lockPath = app(InstallationState::class)->lockPath();

        if (file_exists($lockPath)) {
            unlink($lockPath);
        }

        parent::tearDown();
    }

    public function test_installer_is_visible_before_installation(): void
    {
        config(['installer.locked' => false]);

        $this->get('/install')
            ->assertOk()
            ->assertSee('Install LifeWheel SaaS')
            ->assertSee('Server Checks');
    }

    public function test_installer_is_hidden_when_installation_flag_is_locked(): void
    {
        config(['installer.locked' => true]);

        $this->get('/install')->assertNotFound();
        $this->post('/install/check', [])->assertNotFound();
        $this->post('/install', [])->assertNotFound();
    }

    public function test_installer_is_hidden_when_users_already_exist(): void
    {
        config(['installer.locked' => false]);
        User::factory()->create();

        $this->get('/install')->assertNotFound();
    }
}
