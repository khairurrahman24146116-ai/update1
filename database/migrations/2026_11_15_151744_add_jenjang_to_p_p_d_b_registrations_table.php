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
        Schema::table('p_p_d_b_registrations', function (Blueprint $table) {
            if (! Schema::hasColumn('p_p_d_b_registrations', 'jenjang')) {
                $table->string('jenjang', 20)->nullable()->after('wave_id')->index();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('p_p_d_b_registrations', function (Blueprint $table) {
            if (Schema::hasColumn('p_p_d_b_registrations', 'jenjang')) {
                $table->dropIndex(['jenjang']);
                $table->dropColumn('jenjang');
            }
        });
    }
};
