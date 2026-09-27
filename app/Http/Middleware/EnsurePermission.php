<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePermission
{
    /**
     * Usage: 'permission:institution.setup' (single) or 'permission:institution.setup,institution.view'
     * (comma-separated — passes if the user has ANY one of the listed permissions).
     */
    public function handle(Request $request, Closure $next, string $permissions): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(403);
        }

        $required = explode(',', $permissions);

        $hasAny = collect($required)->contains(fn ($permission) => $user->hasPermission(trim($permission)));

        if (! $hasAny) {
            abort(403, 'You do not have permission to access this page.');
        }

        return $next($request);
    }
}
