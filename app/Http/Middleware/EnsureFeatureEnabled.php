<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureFeatureEnabled
{
    /**
     * Usage: 'feature:courses' — Super Admin bypasses this entirely (they manage the
     * toggles, so a disabled feature never blocks them from configuring it).
     */
    public function handle(Request $request, Closure $next, string $featureKey): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(403);
        }

        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        $institution = $user->institution;

        abort_unless(
            $institution && $institution->hasFeature($featureKey),
            403,
            'This feature is not enabled for your institution. Contact your Administrator.'
        );

        return $next($request);
    }
}
