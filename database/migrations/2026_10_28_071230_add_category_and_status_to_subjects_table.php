<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->dropUnique(['code']);
        });

        Schema::table('subjects', function (Blueprint $table) {
            $table->string('code', 20)->nullable()->change();
            $table->string('jenis', 20)->default('formal')->after('name')->index();
            $table->boolean('is_active')->default(true)->after('jenis')->index();
        });
    }

    public function down(): void
    {
        DB::table('subjects')->whereNull('code')->orderBy('id')->chunkById(100, function ($subjects): void {
            foreach ($subjects as $subject) {
                DB::table('subjects')->where('id', $subject->id)->update([
                    'code' => 'SUBJ-'.$subject->id,
                ]);
            }
        });

        Schema::table('subjects', function (Blueprint $table) {
            $table->dropIndex(['jenis']);
            $table->dropIndex(['is_active']);
            $table->dropColumn(['jenis', 'is_active']);
        });

        Schema::table('subjects', function (Blueprint $table) {
            $table->string('code', 20)->nullable(false)->change();
            $table->unique('code');
        });
    }
};
