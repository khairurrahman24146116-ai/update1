<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_permits', function (Blueprint $table) {
            $table->timestamp('returned_at')->nullable()->after('decided_at')->index();
            $table->foreignId('returned_by')->nullable()->after('returned_at')->constrained('users')->nullOnDelete();
            $table->index(['status', 'end_date', 'returned_at'], 'student_permits_return_scan_index');
        });
    }

    public function down(): void
    {
        Schema::table('student_permits', function (Blueprint $table) {
            $table->dropIndex('student_permits_return_scan_index');
            $table->dropConstrainedForeignId('returned_by');
            $table->dropColumn('returned_at');
        });
    }
};
