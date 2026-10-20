<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_years', function (Blueprint $table) {
            $table->id();
            $table->string('name', 20)->unique();
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->boolean('is_active')->default(false)->index();
            $table->timestamps();
        });

        $now = now();
        $years = DB::table('classrooms')
            ->select('academic_year')
            ->distinct()
            ->pluck('academic_year')
            ->filter()
            ->push('2026/2027')
            ->unique()
            ->values();

        foreach ($years as $name) {
            DB::table('academic_years')->insertOrIgnore([
                'name' => $name,
                'is_active' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        if (! DB::table('academic_years')->where('is_active', true)->exists()) {
            DB::table('academic_years')
                ->where('name', '2026/2027')
                ->update(['is_active' => true, 'updated_at' => $now]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_years');
    }
};
