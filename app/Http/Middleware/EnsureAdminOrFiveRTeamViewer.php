<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminOrFiveRTeamViewer
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->canViewAdminData()) {
            abort(403, 'Akses ditolak. Hanya admin atau tim 5R dapat melihat data ini.');
        }

        return $next($request);
    }
}
