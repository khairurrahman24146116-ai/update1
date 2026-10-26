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
        Schema::table('classrooms', function (Blueprint $table) {
            $table->foreignId('unit_id')->nullable()->after('unit')->constrained('units')->nullOnDelete();
            $table->foreignId('wali_kelas_id')->nullable()->after('unit_id')->constrained('teachers')->nullOnDelete();
        });

        $unitIds = DB::table('units')->pluck('id', 'kode')->all();

        foreach (DB::table('classrooms')->select(['id', 'unit'])->whereNotNull('unit')->get() as $classroom) {
            $unitId = $unitIds[strtoupper((string) $classroom->unit)] ?? null;

            if ($unitId === null) {
                continue;
            }

            DB::table('classrooms')->where('id', $classroom->id)->update(['unit_id' => $unitId]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('classrooms', function (Blueprint $table) {
            $table->dropForeign(['unit_id']);
            $table->dropForeign(['wali_kelas_id']);
            $table->dropColumn(['unit_id', 'wali_kelas_id']);
        });
    }
};
