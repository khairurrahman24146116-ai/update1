<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

trait ResolvesFinanceUnit
{
    /**
     * Unit yang boleh diakses berdasarkan akun terautentikasi.
     * Admin boleh memilih unit lewat query/body, bendahara selalu terikat
     * pada unit akunnya sehingga `unit` dari input tidak pernah dipercaya.
     */
    protected function resolveUnit(Request $request, ?string $requested = null): string
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->isAdmin()) {
            $unit = $requested ?: $request->query('unit') ?: 'sma';
            if (! in_array($unit, ['lpi', 'sma'], true)) {
                throw ValidationException::withMessages(['unit' => 'Unit tidak valid.']);
            }

            return $unit;
        }

        $unit = $user->financeUnit();
        if (! $unit) {
            abort(403, 'Akun tidak memiliki akses keuangan.');
        }

        return $unit;
    }

    /**
     * Pastikan record milik unit akun. Admin utama boleh lintas unit.
     */
    protected function authorizeUnit(Request $request, string $recordUnit): void
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->isAdmin()) {
            return;
        }

        if ($user->financeUnit() !== $recordUnit) {
            abort(403, 'Data bukan milik unit akun ini.');
        }
    }
}
