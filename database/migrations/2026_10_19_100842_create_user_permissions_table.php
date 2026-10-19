<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Override izin per akun di atas izin bawaan role.
     *
     * Hanya baris override yang disimpan (allow/deny). Izin efektif dihitung
     * dari: izin bawaan role + allow - deny. Dengan begitu perubahan role di
     * masa depan tetap konsisten dan tabel tidak menyimpan data duplikat.
     */
    public function up(): void
    {
        Schema::create('user_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('permission', 120);
            $table->string('effect', 5)->default('allow');
            $table->foreignId('granted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'permission']);
            $table->index(['permission', 'effect']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_permissions');
    }
};
