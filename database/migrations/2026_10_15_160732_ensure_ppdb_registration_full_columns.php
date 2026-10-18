<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('p_p_d_b_registrations', function (Blueprint $table) {
            $cols = [
                'registration_number' => fn () => $table->string('registration_number', 30)->nullable()->after('wave_id'),
                'nik' => fn () => $table->string('nik', 20)->nullable()->after('name'),
                'gender' => fn () => $table->string('gender', 10)->nullable()->after('nik'),
                'birth_place' => fn () => $table->string('birth_place', 100)->nullable()->after('gender'),
                'birth_date' => fn () => $table->date('birth_date')->nullable()->after('birth_place'),
                'nationality' => fn () => $table->string('nationality', 50)->nullable()->after('birth_date'),
                'nationality_other' => fn () => $table->string('nationality_other', 100)->nullable()->after('nationality'),
                'child_order' => fn () => $table->unsignedTinyInteger('child_order')->nullable()->after('nationality_other'),
                'sibling_count' => fn () => $table->unsignedTinyInteger('sibling_count')->nullable()->after('child_order'),
                'orphan_status' => fn () => $table->string('orphan_status', 30)->nullable()->after('sibling_count'),
                'daily_language' => fn () => $table->string('daily_language', 50)->nullable()->after('orphan_status'),
                'daily_language_other' => fn () => $table->string('daily_language_other', 100)->nullable()->after('daily_language'),
                'village' => fn () => $table->string('village', 100)->nullable()->after('address'),
                'district' => fn () => $table->string('district', 100)->nullable()->after('village'),
                'city' => fn () => $table->string('city', 100)->nullable()->after('district'),
                'province' => fn () => $table->string('province', 100)->nullable()->after('city'),
                'postal_code' => fn () => $table->string('postal_code', 10)->nullable()->after('province'),
                'photo_path' => fn () => $table->string('photo_path')->nullable()->after('postal_code'),
                'origin_school_name' => fn () => $table->string('origin_school_name', 150)->nullable()->after('photo_path'),
                'origin_school_address' => fn () => $table->text('origin_school_address')->nullable()->after('origin_school_name'),
            ];
            foreach ($cols as $name => $def) {
                if (! Schema::hasColumn('p_p_d_b_registrations', $name)) {
                    $def();
                }
            }
        });

        Schema::table('p_p_d_b_registrations', function (Blueprint $table) {
            $flat = [
                'father_family_card' => ['string', 20], 'father_name' => ['string', 255], 'father_nik' => ['string', 20],
                'father_birth_place' => ['string', 100], 'father_nationality' => ['string', 50], 'father_nationality_other' => ['string', 100],
                'father_education' => ['string', 100], 'father_occupation' => ['string', 100], 'father_phone' => ['string', 20],
                'father_village' => ['string', 100], 'father_district' => ['string', 100], 'father_city' => ['string', 100],
                'father_province' => ['string', 100], 'father_postal_code' => ['string', 10],
                'father_death_place' => ['string', 100], 'father_death_cause' => ['string', 150],
                'mother_family_card' => ['string', 20], 'mother_name' => ['string', 255], 'mother_nik' => ['string', 20],
                'mother_birth_place' => ['string', 100], 'mother_nationality' => ['string', 50], 'mother_nationality_other' => ['string', 100],
                'mother_education' => ['string', 100], 'mother_occupation' => ['string', 100], 'mother_phone' => ['string', 20],
                'mother_village' => ['string', 100], 'mother_district' => ['string', 100], 'mother_city' => ['string', 100],
                'mother_province' => ['string', 100], 'mother_postal_code' => ['string', 10],
                'mother_death_place' => ['string', 100], 'mother_death_cause' => ['string', 150],
                'financier_source' => ['string', 30], 'financier_name' => ['string', 255], 'financier_birth_place' => ['string', 100],
                'financier_nationality' => ['string', 50], 'financier_nationality_other' => ['string', 100],
                'financier_education' => ['string', 100], 'financier_occupation' => ['string', 100], 'financier_phone' => ['string', 20],
                'financier_village' => ['string', 100], 'financier_district' => ['string', 100], 'financier_city' => ['string', 100],
                'financier_province' => ['string', 100], 'financier_postal_code' => ['string', 10], 'financier_relation' => ['string', 100],
                'emergency_name' => ['string', 255], 'emergency_birth_place' => ['string', 100],
                'emergency_nationality' => ['string', 50], 'emergency_nationality_other' => ['string', 100],
                'emergency_education' => ['string', 100], 'emergency_occupation' => ['string', 100], 'emergency_phone' => ['string', 20],
                'emergency_relation' => ['string', 100],
                'guardian_name' => ['string', 255], 'guardian_birth_place' => ['string', 100],
                'guardian_nationality' => ['string', 50], 'guardian_nationality_other' => ['string', 100],
                'guardian_education' => ['string', 100], 'guardian_occupation' => ['string', 100], 'guardian_phone' => ['string', 20],
                'guardian_relation' => ['string', 100],
                'mahram_name' => ['string', 255], 'mahram_birth_place' => ['string', 100],
                'mahram_nationality' => ['string', 50], 'mahram_nationality_other' => ['string', 100],
                'mahram_education' => ['string', 100], 'mahram_occupation' => ['string', 100], 'mahram_phone' => ['string', 20],
                'mahram_relation' => ['string', 100],
                'blood_type' => ['string', 15], 'medical_history_other' => ['string', 255], 'declarant_name' => ['string', 255],
            ];
            foreach ($flat as $name => [$type, $len]) {
                if (! Schema::hasColumn('p_p_d_b_registrations', $name)) {
                    $table->string($name, $len)->nullable();
                }
            }
            $dates = [
                'father_birth_date', 'father_death_date', 'mother_birth_date', 'mother_death_date',
                'financier_birth_date', 'emergency_birth_date', 'guardian_birth_date', 'mahram_birth_date', 'declaration_date',
            ];
            foreach ($dates as $name) {
                if (! Schema::hasColumn('p_p_d_b_registrations', $name)) {
                    $table->date($name)->nullable();
                }
            }
            $texts = [
                'father_address', 'mother_address', 'financier_address',
                'emergency_address', 'guardian_address', 'mahram_address', 'medical_history_note',
            ];
            foreach ($texts as $name) {
                if (! Schema::hasColumn('p_p_d_b_registrations', $name)) {
                    $table->text($name)->nullable();
                }
            }
            $bools = ['father_is_deceased', 'mother_is_deceased', 'guardian_not_required'];
            foreach ($bools as $name) {
                if (! Schema::hasColumn('p_p_d_b_registrations', $name)) {
                    $table->boolean($name)->default(false);
                }
            }
            if (! Schema::hasColumn('p_p_d_b_registrations', 'medical_history')) {
                $table->json('medical_history')->nullable();
            }
            if (! Schema::hasColumn('p_p_d_b_registrations', 'consent_at')) {
                $table->timestamp('consent_at')->nullable();
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
        //
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
