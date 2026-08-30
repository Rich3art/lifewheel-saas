<?php

namespace App\Http\Middleware;

use App\Services\EntitlementService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureFeatureEntitlement
{
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        abort_unless($request->user() && app(EntitlementService::class)->userHasFeature($request->user(), $feature), 403);

        return $next($request);
    }
}
