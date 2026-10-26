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
        Schema::table('users', function (Blueprint $table) {
            $table->index('role');
        });

        DB::table('users')->where('role', 'admin')->update(['role' => 'admin_utama']);
        DB::table('users')->whereIn('role', ['bendahara_lpi', 'bendahara_sma'])->update(['role' => 'bendahara']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('users')->where('role', 'admin_utama')->update(['role' => 'admin']);

        DB::table('users')->where('role', 'bendahara')->where('unit', 'lpi')->update(['role' => 'bendahara_lpi']);
        DB::table('users')->where('role', 'bendahara')->where('unit', 'sma')->update(['role' => 'bendahara_sma']);

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
        });
    }
};
