<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Pastikan selalu ada Admin Utama (akun admin terproteksi) agar hak akses
     * tidak dapat terkunci total. Hanya menandai bila belum ada; tidak
     * menghapus atau mengubah data lain.
     */
    public function up(): void
    {
        $hasProtected = DB::table('users')
            ->where('role', 'admin')
            ->where('is_protected', true)
            ->exists();

        if ($hasProtected) {
            return;
        }

        $adminId = DB::table('users')
            ->where('role', 'admin')
            ->orderBy('id')
            ->value('id');

        if ($adminId) {
            DB::table('users')->where('id', $adminId)->update(['is_protected' => true]);
        }
    }

    public function down(): void
    {
        //
    }
};
