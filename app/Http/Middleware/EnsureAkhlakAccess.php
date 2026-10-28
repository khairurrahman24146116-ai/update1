<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAkhlakAccess
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401, 'Unauthenticated');
        }

        if (! $user->canManageAkhlak()) {
            abort(403, 'Fitur Akhlak & Izin hanya untuk Admin Utama atau Kabag Pengajian LPI.');
        }

        return $next($request);
    }
}
