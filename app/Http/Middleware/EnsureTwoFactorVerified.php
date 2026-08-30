<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureTwoFactorVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->hasEnabledTwoFactorAuthentication() && ! $request->session()->get('auth.two_factor_confirmed')) {
            return redirect()->route('two-factor.challenge');
        }

        return $next($request);
    }
}
