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
        Schema::create('user_unit', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('unit_id')->constrained('units')->cascadeOnDelete();
            $table->string('jabatan')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'unit_id']);
            $table->index('unit_id');
        });

        Schema::create('teacher_unit', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
            $table->foreignId('unit_id')->constrained('units')->cascadeOnDelete();
            $table->string('jabatan')->nullable();
            $table->timestamps();

            $table->unique(['teacher_id', 'unit_id']);
            $table->index('unit_id');
        });

        $this->backfillUserUnits();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher_unit');
        Schema::dropIfExists('user_unit');
    }

    private function backfillUserUnits(): void
    {
        $unitIds = DB::table('units')->pluck('id', 'kode')->all();

        $users = DB::table('users')
            ->select(['id', 'unit'])
            ->whereNotNull('unit')
            ->get();

        foreach ($users as $user) {
            $unitId = $unitIds[strtoupper((string) $user->unit)] ?? null;

            if ($unitId === null) {
                continue;
            }

            DB::table('user_unit')->insertOrIgnore([
                'user_id' => $user->id,
                'unit_id' => $unitId,
                'jabatan' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
};
