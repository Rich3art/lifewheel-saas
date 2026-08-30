<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use App\Support\Totp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class TwoFactorAuthenticationController extends Controller
{
    public function show(Request $request): View
    {
        $this->authorize('manageSecurity', $request->user());

        $user = $request->user();
        $secret = $user->two_factor_secret ?: Totp::generateSecret();

        if (! $user->two_factor_secret) {
            $user->forceFill(['two_factor_secret' => $secret])->save();
        }

        return view('security.two-factor', [
            'user' => $user->fresh(),
            'secret' => $secret,
            'otpauthUrl' => Totp::otpauthUrl(config('app.name', 'LifeWheel SaaS'), $user->email, $secret),
        ]);
    }

    public function confirm(Request $request, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('manageSecurity', $request->user());

        $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        $user = $request->user();

        if (! $user->two_factor_secret || ! Totp::verify($user->two_factor_secret, $request->string('code')->toString())) {
            throw ValidationException::withMessages(['code' => 'The authentication code is not valid.']);
        }

        $user->forceFill([
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => Totp::recoveryCodes(),
        ])->save();

        $request->session()->put('auth.two_factor_confirmed', true);
        $audit->log('security.two_factor_enabled', $user, $user);

        return back()->with('status', 'two-factor-enabled');
    }

    public function disable(Request $request, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('manageSecurity', $request->user());

        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();
        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        $request->session()->put('auth.two_factor_confirmed', true);
        $audit->log('security.two_factor_disabled', $user, $user);

        return back()->with('status', 'two-factor-disabled');
    }

    public function regenerateRecoveryCodes(Request $request, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('manageSecurity', $request->user());

        abort_unless($request->user()->hasEnabledTwoFactorAuthentication(), 403);

        $request->user()->forceFill([
            'two_factor_recovery_codes' => Totp::recoveryCodes(),
        ])->save();

        $audit->log('security.two_factor_recovery_codes_regenerated', $request->user(), $request->user());

        return back()->with('status', 'recovery-codes-regenerated');
    }

    public function challenge(): View
    {
        return view('auth.two-factor-challenge');
    }

    public function verifyChallenge(Request $request, AuditLogger $audit): RedirectResponse
    {
        $request->validate([
            'code' => ['nullable', 'digits:6', 'required_without:recovery_code'],
            'recovery_code' => ['nullable', 'string', 'required_without:code'],
        ]);

        $user = $request->user();

        if ($request->filled('code') && Totp::verify((string) $user->two_factor_secret, $request->string('code')->toString())) {
            $request->session()->put('auth.two_factor_confirmed', true);
            $audit->log('security.two_factor_challenge_passed', $user, $user, ['method' => 'totp']);

            return redirect()->intended(route('member.dashboard'));
        }

        if ($request->filled('recovery_code') && $this->consumeRecoveryCode($user, $request->string('recovery_code')->toString())) {
            $request->session()->put('auth.two_factor_confirmed', true);
            $audit->log('security.two_factor_challenge_passed', $user, $user, ['method' => 'recovery_code']);

            return redirect()->intended(route('member.dashboard'));
        }

        throw ValidationException::withMessages([
            'code' => 'The authentication code or recovery code is not valid.',
        ]);
    }

    private function consumeRecoveryCode($user, string $providedCode): bool
    {
        $codes = $user->recoveryCodes();

        foreach ($codes as $index => $code) {
            if (hash_equals($code, $providedCode)) {
                unset($codes[$index]);
                $user->forceFill(['two_factor_recovery_codes' => array_values($codes)])->save();

                return true;
            }
        }

        return false;
    }
}
