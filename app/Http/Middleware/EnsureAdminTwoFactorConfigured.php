<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureAdminTwoFactorConfigured
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (
            config('security.force_admin_two_factor', true)
            && $user
            && $user->roles()->where('is_protected', true)->exists()
            && ! $user->hasEnabledTwoFactorAuthentication()
            && $request->path() !== 'security/2fa'
            && $request->routeIs('admin.*')
        ) {
            return redirect()->route('two-factor.show')->with('status', 'admin-two-factor-required');
        }

        return $next($request);
    }
}
