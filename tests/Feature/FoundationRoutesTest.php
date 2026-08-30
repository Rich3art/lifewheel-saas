<?php

namespace Tests\Feature;

use Tests\TestCase;

final class FoundationRoutesTest extends TestCase
{
    public function test_home_page_renders(): void
    {
        $this->get('/')->assertOk()->assertSee('LifeWheel SaaS');
    }

    public function test_health_endpoint_returns_ok(): void
    {
        $this->getJson('/health')
            ->assertOk()
            ->assertJson(['status' => 'ok']);
    }

    public function test_admin_and_member_shells_render_without_product_features(): void
    {
        $this->get('/admin/dashboard')->assertOk()->assertSee('Admin dashboard foundation');
        $this->get('/app/dashboard')->assertOk()->assertSee('Member dashboard foundation');
    }
}
