<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsurePasswordChanged
{
    public function handle(Request $request, Closure $next)
    {
        if (
            auth()->check() &&
            auth()->user()->must_change_password &&
            !$request->routeIs('password.force.form') &&
            !$request->routeIs('password.force.update') &&
            !$request->routeIs('auth.logout')
        ) {
            return response('', 302)->header('Location', '/force-change-password');
        }

        return $next($request);
    }
}
