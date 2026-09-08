<?php

namespace Tests\Feature\Pwa;

use Tests\TestCase;

final class PwaAssetsTest extends TestCase
{
    public function test_app_layout_exposes_pwa_metadata(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('/manifest.webmanifest', false)
            ->assertSee('theme-color', false)
            ->assertSee('/icons/icon.svg', false);
    }

    public function test_manifest_is_installable(): void
    {
        $this->get('/manifest.webmanifest')
            ->assertOk()
            ->assertJsonPath('name', 'LifeWheel SaaS')
            ->assertJsonPath('display', 'standalone')
            ->assertJsonPath('start_url', '/app/dashboard')
            ->assertJsonPath('icons.0.src', '/icons/icon.svg');
    }

    public function test_service_worker_and_offline_page_are_available(): void
    {
        $this->get('/sw.js')
            ->assertOk()
            ->assertSee('lifewheel-pwa-v1', false)
            ->assertSee('/offline.html', false);

        $this->get('/offline.html')
            ->assertOk()
            ->assertSee('LifeWheel is offline');
    }
}
