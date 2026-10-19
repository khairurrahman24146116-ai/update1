<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * Mendukung beberapa peran sekaligus: role:admin,bendahara_lpi,bendahara_sma
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $role = $request->user()?->role;

        if (! $role || ! in_array($role, $roles, true)) {
            abort(403, 'Unauthorized');
        }

        return $next($request);
    }
}
