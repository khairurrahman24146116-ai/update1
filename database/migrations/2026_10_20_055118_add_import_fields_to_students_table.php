<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->date('tanggal_lahir')->nullable()->after('gender');
            $table->string('asal_siswa', 10)->nullable()->after('tanggal_lahir');
            $table->string('nama_ayah')->nullable()->after('asal_siswa');
            $table->string('nama_ibu')->nullable()->after('nama_ayah');
            $table->string('nama_wali')->nullable()->after('nama_ibu');
            $table->string('no_hp_wali', 20)->nullable()->after('nama_wali')->index();
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropIndex(['no_hp_wali']);
            $table->dropColumn([
                'tanggal_lahir',
                'asal_siswa',
                'nama_ayah',
                'nama_ibu',
                'nama_wali',
                'no_hp_wali',
            ]);
        });
    }
};
