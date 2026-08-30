<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

final class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request, AuditLogger $audit): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        $user = $request->user();
        $user?->forceFill(['last_login_at' => now()])->save();
        $audit->log('auth.login', $user, $user);

        if ($user?->hasEnabledTwoFactorAuthentication()) {
            $request->session()->forget('auth.two_factor_confirmed');

            return redirect()->route('two-factor.challenge');
        }

        $request->session()->put('auth.two_factor_confirmed', true);

        return redirect()->intended(route('member.dashboard'));
    }

    public function destroy(Request $request, AuditLogger $audit): RedirectResponse
    {
        $user = $request->user();
        $audit->log('auth.logout', $user, $user);

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
