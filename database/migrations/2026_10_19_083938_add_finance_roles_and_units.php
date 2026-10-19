<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pisahkan peran bendahara menjadi bendahara_lpi dan bendahara_sma serta
     * tandai unit pada akun & kelas. Kolom role diubah menjadi string agar
     * penambahan peran baru tidak memerlukan ALTER ENUM yang rapuh.
     *
     * Akun bendahara lama dipetakan ke unit SMA karena aplikasi ini sejak awal
     * melayani keuangan SMA Madani Al-Aziziyah. Pemetaan hanya menyentuh akun
     * berperan bendahara, bukan data siswa atau tagihan.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 32)->default('wali_murid')->change();
            $table->string('unit', 10)->nullable()->after('role')->index();
        });

        Schema::table('classrooms', function (Blueprint $table) {
            $table->string('unit', 10)->default('sma')->after('academic_year')->index();
        });

        DB::table('users')
            ->where('role', 'bendahara')
            ->update(['role' => 'bendahara_sma', 'unit' => 'sma']);
    }

    public function down(): void
    {
        DB::table('users')
            ->whereIn('role', ['bendahara_lpi', 'bendahara_sma'])
            ->update(['role' => 'bendahara']);

        Schema::table('classrooms', function (Blueprint $table) {
            $table->dropIndex(['unit']);
            $table->dropColumn('unit');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['unit']);
            $table->dropColumn('unit');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'bendahara', 'guru', 'wali_murid'])->default('wali_murid')->change();
        });
    }
};
