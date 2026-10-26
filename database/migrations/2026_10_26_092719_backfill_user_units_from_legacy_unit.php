<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Salin unit lama ke user_unit. Tidak mengubah atau menghapus users.unit.
     *
     * Sumber:
     * - users.unit (LPI/SMA/SMP)
     * - role keuangan lama: bendahara_lpi -> LPI, bendahara_sma -> SMA
     *
     * Admin (termasuk admin_utama) dan operator dilewati karena tidak dibatasi unit.
     */
    public function up(): void
    {
        $unitIds = DB::table('units')
            ->get(['id', 'kode'])
            ->mapWithKeys(fn (object $unit): array => [strtoupper((string) $unit->kode) => (int) $unit->id])
            ->all();

        DB::table('users')
            ->select(['id', 'role', 'unit'])
            ->whereNotIn('role', ['admin', 'admin_utama', 'operator'])
            ->orderBy('id')
            ->chunkById(200, function ($users) use ($unitIds): void {
                foreach ($users as $user) {
                    $code = $this->resolveUnitCode((string) $user->role, $user->unit);

                    if ($code === null || ! isset($unitIds[$code])) {
                        continue;
                    }

                    DB::table('user_unit')->insertOrIgnore([
                        'user_id' => $user->id,
                        'unit_id' => $unitIds[$code],
                        'jabatan' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }, 'id');
    }

    /**
     * Backfill tidak dihapus saat rollback karena baris user_unit bisa saja
     * sudah ada sebelum migration ini dan tidak dapat dibedakan asalnya.
     */
    public function down(): void {}

    private function resolveUnitCode(string $role, ?string $unit): ?string
    {
        return match ($role) {
            'bendahara_lpi' => 'LPI',
            'bendahara_sma' => 'SMA',
            default => $this->normalizeUnitCode($unit),
        };
    }

    private function normalizeUnitCode(?string $unit): ?string
    {
        if ($unit === null || trim($unit) === '') {
            return null;
        }

        return strtoupper(trim($unit));
    }
};
