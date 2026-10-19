<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PermissionMiddleware
{
    /**
     * Mendukung beberapa izin sekaligus (logika OR).
     * Contoh: permission:students.view,students.update
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401, 'Unauthenticated');
        }

        if (! $user->hasAnyPermission($permissions)) {
            abort(403, 'Anda tidak memiliki izin untuk tindakan ini.');
        }

        return $next($request);
    }
}
