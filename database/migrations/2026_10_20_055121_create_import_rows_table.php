<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('import_batches')->cascadeOnDelete();
            $table->unsignedInteger('nomor_baris');
            $table->json('data_mentah');
            $table->json('data_valid')->nullable();
            $table->string('status', 20)->default('valid')->index();
            $table->json('pesan')->nullable();
            $table->string('aksi', 20)->nullable();
            $table->foreignId('student_id')->nullable()->constrained('students')->nullOnDelete();
            $table->timestamps();

            $table->unique(['batch_id', 'nomor_baris']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_rows');
    }
};
