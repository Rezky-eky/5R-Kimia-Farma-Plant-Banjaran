<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ?string $guard = null): Response
    {
        $guardToUse = $guard ?? 'web';

        if (Auth::guard($guardToUse)->check()) {
            // Redirect eksplisit ke dashboard tanpa bergantung pada intended URL
            return redirect('/dashboard');
        }

        return $next($request);
    }
}