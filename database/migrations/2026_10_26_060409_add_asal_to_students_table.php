<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->enum('asal', ['luar', 'internal'])->nullable()->after('asal_siswa')->index();
        });

        DB::table('students')->where('asal_siswa', 'internal')->update(['asal' => 'internal']);
        DB::table('students')->whereIn('asal_siswa', ['ppdb', 'luar'])->update(['asal' => 'luar']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropIndex(['asal']);
            $table->dropColumn('asal');
        });
    }
};
