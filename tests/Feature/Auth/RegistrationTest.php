<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $this->get('/register')->assertOk()->assertSee('Create your account');
    }

    public function test_new_users_can_register(): void
    {
        $this->post('/register', [
            'name' => 'Jenny Smith',
            'email' => 'jenny@example.com',
            'timezone' => 'Asia/Dubai',
            'password' => 'secure-password',
            'password_confirmation' => 'secure-password',
        ])->assertRedirect(route('verification.notice', absolute: false));

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'jenny@example.com']);
    }
}
