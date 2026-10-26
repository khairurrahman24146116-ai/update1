<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUnitAccess
{
    /**
     * Batasi akses ke unit yang disebut pada route, body, atau query.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401, 'Unauthenticated');
        }

        $unit = $request->route('unit')
            ?? $request->input('unit')
            ?? $request->query('unit');

        if ($unit === null || $unit === '') {
            return $next($request);
        }

        if (! $user->canAccessDataUnit((string) $unit)) {
            abort(403, 'Anda tidak memiliki akses ke unit ini.');
        }

        return $next($request);
    }
}
