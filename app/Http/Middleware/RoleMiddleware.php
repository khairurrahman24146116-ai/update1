<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Mendukung beberapa peran sekaligus: role:admin_utama,bendahara_lpi
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $role = $request->user()?->role;

        if (! $role || ! $this->matchesAny($role, $roles)) {
            abort(403, 'Unauthorized');
        }

        return $next($request);
    }

    /**
     * @param  list<string>  $allowed
     */
    private function matchesAny(string $role, array $allowed): bool
    {
        foreach ($allowed as $candidate) {
            if ($role === $candidate) {
                return true;
            }

            if (in_array($role, $this->aliases($candidate), true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private function aliases(string $role): array
    {
        return match ($role) {
            'bendahara' => ['bendahara_lpi', 'bendahara_sma'],
            'bendahara_lpi', 'bendahara_sma' => ['bendahara'],
            default => [],
        };
    }
}
