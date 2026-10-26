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
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 10)->unique();
            $table->string('nama');
            $table->timestamps();
        });

        $now = now();

        foreach ([
            ['kode' => 'LPI', 'nama' => 'LPI Dayah Madani Al-Aziziyah'],
            ['kode' => 'SMA', 'nama' => 'SMA Dayah Madani Al-Aziziyah'],
            ['kode' => 'SMP', 'nama' => 'SMP Dayah Madani Al-Aziziyah'],
        ] as $unit) {
            DB::table('units')->insert([
                ...$unit,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
