<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureGoCheckManagement
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->canManageGoCheck()) {
            abort(403, 'Akses khusus manajemen 5R (Ketua/Sekretaris/Admin).');
        }

        return $next($request);
    }
}
