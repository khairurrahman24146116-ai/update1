<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRoleAssigned
{
    /**
     * Akun berstatus pending hanya boleh melihat identitasnya sendiri dan
     * logout. Seluruh endpoint data lain ditolak sampai admin utama
     * menetapkan role.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->isPending() && ! $this->isAllowedPath($request)) {
            return response()->json([
                'message' => 'Akun Anda menunggu penetapan hak akses oleh Admin Utama.',
                'code' => 'pending_role',
            ], 403);
        }

        return $next($request);
    }

    private function isAllowedPath(Request $request): bool
    {
        return $request->is('api/me', 'api/user', 'api/logout');
    }
}
