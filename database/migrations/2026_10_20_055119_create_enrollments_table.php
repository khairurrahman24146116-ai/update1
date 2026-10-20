<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('classrooms')->restrictOnDelete();
            $table->foreignId('tahun_ajaran_id')->constrained('academic_years')->restrictOnDelete();
            $table->string('jalur', 10);
            $table->string('status', 20)->default('aktif');
            $table->timestamps();

            $table->unique(['student_id', 'class_id', 'tahun_ajaran_id'], 'enrollments_student_class_year_unique');
            $table->unique(['student_id', 'tahun_ajaran_id', 'jalur'], 'enrollments_student_year_jalur_unique');
            $table->index(['class_id', 'tahun_ajaran_id', 'status'], 'enrollments_class_year_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollments');
    }
};
