<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Pastikan role admin lama menjadi admin_utama. Migrasi sebelumnya sudah
     * memetakan nilai ini, tetapi akun yang dibuat setelah migrasi tersebut
     * masih dapat memakai role lama. Migrasi ini idempoten dan aman dijalankan
     * berulang.
     */
    public function up(): void
    {
        DB::table('users')
            ->where('role', 'admin')
            ->update(['role' => 'admin_utama']);
    }

    public function down(): void
    {
        // Sengaja tidak dibalik: memisahkan kembali admin_utama menjadi admin
        // berisiko menurunkan proteksi akun utama yang sudah ditetapkan.
    }
};
