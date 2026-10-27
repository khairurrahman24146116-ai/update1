<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('site_settings', 'activity_log_retention_months')) {
            return;
        }

        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn('activity_log_retention_months');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('site_settings', 'activity_log_retention_months')) {
            return;
        }

        Schema::table('site_settings', function (Blueprint $table) {
            $table->unsignedSmallInteger('activity_log_retention_months')->default(12);
        });
    }
};
