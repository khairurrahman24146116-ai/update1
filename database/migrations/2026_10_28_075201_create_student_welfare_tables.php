<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_welfare_settings', function (Blueprint $table) {
            $table->id();
            $table->decimal('attendance_min_percent', 5, 2)->default(75);
            $table->unsignedTinyInteger('permit_max_per_month')->default(3);
            $table->unsignedSmallInteger('permit_max_per_semester')->default(12);
            $table->unsignedSmallInteger('violation_warning_points')->default(20);
            $table->unsignedSmallInteger('violation_max_points')->default(50);
            $table->unsignedTinyInteger('point_ringan')->default(5);
            $table->unsignedTinyInteger('point_sedang')->default(15);
            $table->unsignedTinyInteger('point_berat')->default(30);
            $table->boolean('allow_permit_override')->default(true);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('akhlak_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('classroom_id')->constrained()->restrictOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 20)->default('pelanggaran');
            $table->string('severity', 20)->nullable();
            $table->string('category', 100)->nullable();
            $table->text('description');
            $table->unsignedSmallInteger('points')->default(0);
            $table->date('occurred_at')->index();
            $table->string('academic_year', 20)->nullable();
            $table->string('semester', 20)->nullable();
            $table->string('status', 20)->default('active')->index();
            $table->text('internal_note')->nullable();
            $table->text('correction_reason')->nullable();
            $table->timestamp('corrected_at')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'status']);
            $table->index(['classroom_id', 'type']);
        });

        Schema::create('student_permits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('classroom_id')->constrained()->restrictOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 20)->default('izin');
            $table->text('reason');
            $table->date('start_date')->index();
            $table->date('end_date')->index();
            $table->string('status', 20)->default('pending')->index();
            $table->timestamp('decided_at')->nullable();
            $table->text('decision_note')->nullable();
            $table->boolean('is_override')->default(false);
            $table->text('override_reason')->nullable();
            $table->string('academic_year', 20)->nullable();
            $table->string('semester', 20)->nullable();
            $table->timestamps();

            $table->index(['student_id', 'status', 'start_date']);
        });

        Schema::create('student_welfare_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('type', 30);
            $table->string('period', 20);
            $table->string('severity', 20)->default('warning');
            $table->text('message');
            $table->timestamp('resolved_at')->nullable()->index();
            $table->timestamps();

            $table->unique(['student_id', 'type', 'period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_welfare_alerts');
        Schema::dropIfExists('student_permits');
        Schema::dropIfExists('akhlak_records');
        Schema::dropIfExists('student_welfare_settings');
    }
};
