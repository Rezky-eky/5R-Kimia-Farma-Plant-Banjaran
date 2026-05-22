<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureFiveRTeam
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->canActAsGoCheckFinder()) {
            abort(403, 'Fitur Go Check hanya untuk tim 5R terpilih.');
        }

        return $next($request);
    }
}
