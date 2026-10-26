<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Models\User;
use App\Policies\KeuanganLpiPolicy;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

trait ResolvesFinanceUnit
{
    /**
     * Unit yang boleh diakses berdasarkan akun terautentikasi.
     * Admin boleh memilih unit lewat query/body, sedangkan akun lain hanya
     * boleh memakai unit dari user_unit.
     */
    protected function resolveUnit(Request $request, ?string $requested = null): string
    {
        /** @var User $user */
        $user = $request->user();
        $requested = $requested ?: $request->query('unit');

        if ($user->isAdmin()) {
            $unit = $requested ?: 'sma';
            if (! in_array($unit, ['lpi', 'sma'], true)) {
                throw ValidationException::withMessages(['unit' => 'Unit tidak valid.']);
            }

            return $this->guardLpiAccess($user, $unit);
        }

        $allowed = array_values(array_intersect($user->effectiveUnitCodes(), ['lpi', 'sma']));

        if ($allowed === []) {
            abort(403, 'Akun tidak memiliki akses keuangan.');
        }

        if ($requested !== null && $requested !== '') {
            $unit = strtolower(trim($requested));

            if (! in_array($unit, $allowed, true)) {
                abort(403, 'Akun tidak memiliki akses ke unit keuangan tersebut.');
            }

            return $this->guardLpiAccess($user, $unit);
        }

        return $this->guardLpiAccess($user, $allowed[0]);
    }

    /**
     * Pastikan record milik unit akun. Admin utama boleh lintas unit.
     */
    protected function authorizeUnit(Request $request, string $recordUnit): void
    {
        /** @var User $user */
        $user = $request->user();

        if (! $user->isAdmin() && ! in_array(strtolower($recordUnit), $user->effectiveUnitCodes(), true)) {
            abort(403, 'Data bukan milik unit akun ini.');
        }

        $this->guardLpiAccess($user, $recordUnit);
    }

    private function guardLpiAccess(User $user, string $unit): string
    {
        if ($unit === 'lpi' && ! app(KeuanganLpiPolicy::class)->view($user)) {
            abort(403, 'Hanya Admin Utama dan Bendahara LPI yang dapat mengakses keuangan LPI.');
        }

        return $unit;
    }
}
