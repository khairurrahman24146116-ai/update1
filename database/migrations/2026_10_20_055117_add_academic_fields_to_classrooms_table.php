<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('classrooms', function (Blueprint $table) {
            $table->string('jalur', 10)->nullable()->after('unit')->index();
            $table->string('jenjang', 20)->nullable()->after('jalur')->index();
            $table->foreignId('tahun_ajaran_id')->nullable()->after('jenjang')
                ->constrained('academic_years')->nullOnDelete();
        });

        $yearMap = DB::table('academic_years')->pluck('id', 'name')->all();
        $now = now();

        foreach (DB::table('classrooms')->orderBy('id')->get() as $classroom) {
            if (! isset($yearMap[$classroom->academic_year])) {
                $id = DB::table('academic_years')->insertGetId([
                    'name' => $classroom->academic_year,
                    'is_active' => false,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $yearMap[$classroom->academic_year] = $id;
            }

            $unit = $classroom->unit ?: 'sma';
            $grade = (int) $classroom->grade;
            $jalur = $unit === 'lpi' ? 'lpi' : 'formal';
            $jenjang = $unit === 'lpi'
                ? ($grade >= 10 ? 'Aliyah' : 'Tsanawiyah')
                : ($grade >= 10 ? 'SMA' : 'SMP');

            DB::table('classrooms')->where('id', $classroom->id)->update([
                'jalur' => $jalur,
                'jenjang' => $jenjang,
                'tahun_ajaran_id' => $yearMap[$classroom->academic_year] ?? null,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('classrooms', function (Blueprint $table) {
            $table->dropForeign(['tahun_ajaran_id']);
            $table->dropIndex(['jalur']);
            $table->dropIndex(['jenjang']);
            $table->dropColumn(['jalur', 'jenjang', 'tahun_ajaran_id']);
        });
    }
};
