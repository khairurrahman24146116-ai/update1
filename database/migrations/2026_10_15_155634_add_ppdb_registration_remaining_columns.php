<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $shrinks = [
        'registration_number' => 30,
        'birth_place' => 100,
        'nationality' => 50,
        'nationality_other' => 100,
        'orphan_status' => 30,
        'daily_language' => 50,
        'daily_language_other' => 100,
        'village' => 100,
        'district' => 100,
        'city' => 100,
        'province' => 100,
        'origin_school_name' => 150,
        'father_birth_place' => 100,
        'father_nationality' => 50,
        'father_nationality_other' => 100,
        'father_education' => 100,
        'father_occupation' => 100,
        'father_village' => 100,
        'father_district' => 100,
        'father_city' => 100,
        'father_province' => 100,
        'father_death_place' => 100,
        'father_death_cause' => 150,
        'mother_birth_place' => 100,
        'mother_nationality' => 50,
        'mother_nationality_other' => 100,
        'mother_education' => 100,
        'mother_occupation' => 100,
        'mother_village' => 100,
        'mother_district' => 100,
        'mother_city' => 100,
        'mother_province' => 100,
        'mother_death_place' => 100,
        'mother_death_cause' => 150,
        'financier_source' => 30,
        'financier_birth_place' => 100,
        'financier_nationality' => 50,
        'financier_nationality_other' => 100,
        'financier_education' => 100,
        'financier_occupation' => 100,
        'financier_village' => 100,
        'financier_district' => 100,
        'financier_city' => 100,
        'financier_province' => 100,
        'financier_relation' => 100,
        'emergency_birth_place' => 100,
        'emergency_nationality' => 50,
        'emergency_nationality_other' => 100,
        'emergency_education' => 100,
        'emergency_occupation' => 100,
        'emergency_relation' => 100,
        'guardian_birth_place' => 100,
    ];

    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            foreach ($this->shrinks as $col => $len) {
                if (Schema::hasColumn('p_p_d_b_registrations', $col)) {
                    DB::statement("ALTER TABLE `p_p_d_b_registrations` MODIFY `{$col}` VARCHAR({$len}) NULL");
                }
            }
        }

        Schema::table('p_p_d_b_registrations', function (Blueprint $table) {
            if (! Schema::hasColumn('p_p_d_b_registrations', 'guardian_nationality')) {
                $table->string('guardian_nationality', 50)->nullable();
            }
            if (! Schema::hasColumn('p_p_d_b_registrations', 'guardian_nationality_other')) {
                $table->string('guardian_nationality_other', 100)->nullable()->after('guardian_nationality');
            }
            if (! Schema::hasColumn('p_p_d_b_registrations', 'guardian_education')) {
                $table->string('guardian_education', 100)->nullable()->after('guardian_nationality_other');
            }
            if (! Schema::hasColumn('p_p_d_b_registrations', 'guardian_occupation')) {
                $table->string('guardian_occupation', 100)->nullable()->after('guardian_education');
            }
            if (! Schema::hasColumn('p_p_d_b_registrations', 'guardian_phone')) {
                $table->string('guardian_phone', 20)->nullable()->after('guardian_occupation');
            }
            if (! Schema::hasColumn('p_p_d_b_registrations', 'guardian_address')) {
                $table->text('guardian_address')->nullable()->after('guardian_phone');
            }
            if (! Schema::hasColumn('p_p_d_b_registrations', 'guardian_relation')) {
                $table->string('guardian_relation', 100)->nullable()->after('guardian_address');
            }
            if (! Schema::hasColumn('p_p_d_b_registrations', 'mahram_name')) {
                $table->string('mahram_name')->nullable()->after('guardian_relation');
            }
            if (! Schema::hasColumn('p_p_d_b_registrations', 'mahram_birth_place')) {
                $table->string('mahram_birth_place', 100)->nullable()->after('mahram_name');
            }
            if (! Schema::hasColumn('p_p_d_b_registrations', 'mahram_birth_date')) {
                $table->date('mahram_birth_date')->nullable()->after('mahram_birth_place');
            }
            if (! Schema::hasColumn('p_p_d_b_registrations', 'mahram_nationality')) {
                $table->string('mahram_nationality', 50)->nullable()->after('mahram_birth_date');
            }
            if (! Schema::hasColumn('p_p_d_b_registrations', 'mahram_nationality_other')) {
                $table->string('mahram_nationality_other', 100)->nullable()->after('mahram_nationality');
            }
            if (! Schema::hasColumn('p_p_d_b_registrations', 'mahram_education')) {
                $table->string('mahram_education', 100)->nullable()->after('mahram_nationality_other');
            }
            if (! Schema::hasColumn('p_p_d_b_registrations', 'mahram_occupation')) {
                $table->string('mahram_occupation', 100)->nullable()->after('mahram_education');
            }
            if (! Schema::hasColumn('p_p_d_b_registrations', 'mahram_phone')) {
                $table->string('mahram_phone', 20)->nullable()->after('mahram_occupation');
            }
            if (! Schema::hasColumn('p_p_d_b_registrations', 'mahram_address')) {
                $table->text('mahram_address')->nullable()->after('mahram_phone');
            }
            if (! Schema::hasColumn('p_p_d_b_registrations', 'mahram_relation')) {
                $table->string('mahram_relation', 100)->nullable()->after('mahram_address');
            }
            if (! Schema::hasColumn('p_p_d_b_registrations', 'blood_type')) {
                $table->string('blood_type', 15)->nullable()->after('mahram_relation');
            }
            if (! Schema::hasColumn('p_p_d_b_registrations', 'medical_history')) {
                $table->json('medical_history')->nullable()->after('blood_type');
            }
            if (! Schema::hasColumn('p_p_d_b_registrations', 'medical_history_other')) {
                $table->string('medical_history_other')->nullable()->after('medical_history');
            }
            if (! Schema::hasColumn('p_p_d_b_registrations', 'medical_history_note')) {
                $table->text('medical_history_note')->nullable()->after('medical_history_other');
            }
            if (! Schema::hasColumn('p_p_d_b_registrations', 'declarant_name')) {
                $table->string('declarant_name')->nullable()->after('medical_history_note');
            }
            if (! Schema::hasColumn('p_p_d_b_registrations', 'declaration_date')) {
                $table->date('declaration_date')->nullable()->after('declarant_name');
            }
            if (! Schema::hasColumn('p_p_d_b_registrations', 'consent_at')) {
                $table->timestamp('consent_at')->nullable()->after('declaration_date');
            }
        });

        if (Schema::hasColumn('p_p_d_b_registrations', 'registration_number')
            && ! $this->hasUnique('p_p_d_b_registrations', 'registration_number')) {
            Schema::table('p_p_d_b_registrations', function (Blueprint $table) {
                $table->unique('registration_number', 'ppdb_reg_number_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::table('p_p_d_b_registrations', function (Blueprint $table) {
            $table->dropUnique('ppdb_reg_number_unique');
            $table->dropColumn([
                'guardian_nationality', 'guardian_nationality_other', 'guardian_education',
                'guardian_occupation', 'guardian_phone', 'guardian_address', 'guardian_relation',
                'mahram_name', 'mahram_birth_place', 'mahram_birth_date', 'mahram_nationality',
                'mahram_nationality_other', 'mahram_education', 'mahram_occupation', 'mahram_phone',
                'mahram_address', 'mahram_relation',
                'blood_type', 'medical_history', 'medical_history_other', 'medical_history_note',
                'declarant_name', 'declaration_date', 'consent_at',
            ]);
        });
    }

    private function hasUnique(string $table, string $column): bool
    {
        if (DB::getDriverName() !== 'mysql') {
            return true;
        }
        $db = DB::getDatabaseName();

        return (bool) DB::selectOne(
            'SELECT 1 FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND column_name = ? AND non_unique = 0 LIMIT 1',
            [$db, $table, $column]
        );
    }
};
