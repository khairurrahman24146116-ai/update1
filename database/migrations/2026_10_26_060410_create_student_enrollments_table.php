<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('student_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('unit_id')->constrained('units')->restrictOnDelete();
            $table->string('jenjang', 20);
            $table->foreignId('class_id')->nullable()->constrained('classrooms')->nullOnDelete();
            $table->string('tahun_ajaran', 20);
            $table->string('status', 20)->default('aktif');
            $table->string('sumber', 20)->default('manual');
            $table->text('alasan_manual')->nullable();
            $table->foreignId('dibuat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('jenjang');
            $table->index('tahun_ajaran');
            $table->index('status');
            $table->index(['unit_id', 'status']);
            $table->index(['student_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_enrollments');
    }
};
