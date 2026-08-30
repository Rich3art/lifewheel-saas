<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Support\Totp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class TwoFactorAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_two_factor_setup_page_generates_a_secret(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/security/2fa')
            ->assertOk()
            ->assertSee('Set up authenticator');

        $this->assertNotNull($user->fresh()->two_factor_secret);
    }

    public function test_enabled_two_factor_requires_challenge_on_login(): void
    {
        $secret = Totp::generateSecret();
        $user = User::factory()->create([
            'two_factor_secret' => $secret,
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => ['ABCDE-FGHIJ'],
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('two-factor.challenge', absolute: false));
    }

    public function test_recovery_code_allows_two_factor_challenge_once(): void
    {
        $secret = Totp::generateSecret();
        $user = User::factory()->create([
            'two_factor_secret' => $secret,
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => ['ABCDE-FGHIJ'],
        ]);

        $this->actingAs($user)->post('/security/2fa/challenge', [
            'recovery_code' => 'ABCDE-FGHIJ',
        ])->assertRedirect(route('member.dashboard', absolute: false));

        $this->assertSame([], $user->fresh()->recoveryCodes());
    }
}
