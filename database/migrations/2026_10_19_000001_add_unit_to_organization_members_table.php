<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Additive migration: every existing structure row belongs to the LPI chart
     * (that is the only unit currently using it), so the default 'lpi' assigns
     * them without touching any existing value.
     */
    public function up(): void
    {
        Schema::table('organization_members', function (Blueprint $table) {
            $table->string('unit', 10)->default('lpi')->after('id');
            $table->index(['unit', 'parent_id', 'sort_order'], 'org_members_unit_parent_sort_index');
        });
    }

    public function down(): void
    {
        Schema::table('organization_members', function (Blueprint $table) {
            $table->dropIndex('org_members_unit_parent_sort_index');
            $table->dropColumn('unit');
        });
    }
};
