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
        Schema::table('teachers', function (Blueprint $table) {
            $table->string('photo_path')->nullable()->after('nip');
        });

        $this->backfillTeacherProfiles();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->dropColumn('photo_path');
        });
    }

    private function backfillTeacherProfiles(): void
    {
        $users = DB::table('users')
            ->select(['id', 'name'])
            ->whereIn('role', ['guru', 'pending'])
            ->orderBy('id')
            ->get();

        foreach ($users as $user) {
            $teacherId = DB::table('teachers')->where('user_id', $user->id)->value('id');

            if ($teacherId === null) {
                $teacherId = DB::table('teachers')->insertGetId([
                    'user_id' => $user->id,
                    'nama' => $user->name,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                DB::table('teachers')->where('id', $teacherId)->update([
                    'nama' => $user->name,
                    'updated_at' => now(),
                ]);
            }

            $units = DB::table('user_unit')->where('user_id', $user->id)->get();

            foreach ($units as $unit) {
                DB::table('teacher_unit')->insertOrIgnore([
                    'teacher_id' => $teacherId,
                    'unit_id' => $unit->unit_id,
                    'jabatan' => $unit->jabatan,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
};
