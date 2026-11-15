<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PPDBRegistration extends Model
{
    use HasFactory;

    protected $table = 'p_p_d_b_registrations';

    /**
     * @var array<string, string>
     */
    public const JENJANG_LABELS = [
        'lanjut' => 'Jenjang Lanjut',
        'sma' => 'Pendaftaran SMA',
        'smp' => 'Pendaftaran SMP',
        'salafiyah' => 'Salafiyah Santri Putri',
    ];

    protected $fillable = [
        'wave_id', 'jenjang', 'registration_number',
        'name', 'nik', 'gender', 'birth_place', 'birth_date',
        'nationality', 'nationality_other', 'child_order', 'sibling_count', 'orphan_status',
        'daily_language', 'daily_language_other',
        'nisn', 'phone', 'email', 'address', 'village', 'district', 'city', 'province', 'postal_code',
        'photo_path', 'origin_school_name', 'origin_school_address',
        'father_family_card', 'father_name', 'father_nik', 'father_birth_place', 'father_birth_date',
        'father_nationality', 'father_nationality_other', 'father_education', 'father_occupation',
        'father_phone', 'father_address', 'father_village', 'father_district', 'father_city',
        'father_province', 'father_postal_code', 'father_is_deceased', 'father_death_date',
        'father_death_place', 'father_death_cause',
        'mother_family_card', 'mother_name', 'mother_nik', 'mother_birth_place', 'mother_birth_date',
        'mother_nationality', 'mother_nationality_other', 'mother_education', 'mother_occupation',
        'mother_phone', 'mother_address', 'mother_village', 'mother_district', 'mother_city',
        'mother_province', 'mother_postal_code', 'mother_is_deceased', 'mother_death_date',
        'mother_death_place', 'mother_death_cause',
        'financier_source', 'financier_name', 'financier_birth_place', 'financier_birth_date',
        'financier_nationality', 'financier_nationality_other', 'financier_education',
        'financier_occupation', 'financier_phone', 'financier_address', 'financier_village',
        'financier_district', 'financier_city', 'financier_province', 'financier_postal_code',
        'financier_relation',
        'emergency_name', 'emergency_birth_place', 'emergency_birth_date', 'emergency_nationality',
        'emergency_nationality_other', 'emergency_education', 'emergency_occupation',
        'emergency_phone', 'emergency_address', 'emergency_relation',
        'guardian_not_required', 'guardian_name', 'guardian_birth_place', 'guardian_birth_date',
        'guardian_nationality', 'guardian_nationality_other', 'guardian_education',
        'guardian_occupation', 'guardian_phone', 'guardian_address', 'guardian_relation',
        'mahram_name', 'mahram_birth_place', 'mahram_birth_date', 'mahram_nationality',
        'mahram_nationality_other', 'mahram_education', 'mahram_occupation', 'mahram_phone',
        'mahram_address', 'mahram_relation',
        'blood_type', 'medical_history', 'medical_history_other', 'medical_history_note',
        'declarant_name', 'declaration_date', 'consent_at',
        'status', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'father_birth_date' => 'date',
            'father_is_deceased' => 'boolean',
            'father_death_date' => 'date',
            'mother_birth_date' => 'date',
            'mother_is_deceased' => 'boolean',
            'mother_death_date' => 'date',
            'financier_birth_date' => 'date',
            'emergency_birth_date' => 'date',
            'guardian_not_required' => 'boolean',
            'guardian_birth_date' => 'date',
            'mahram_birth_date' => 'date',
            'medical_history' => 'array',
            'declaration_date' => 'date',
            'consent_at' => 'datetime',
        ];
    }

    public function wave(): BelongsTo
    {
        return $this->belongsTo(PPDBWave::class, 'wave_id');
    }

    public function jenjangLabel(): string
    {
        return self::JENJANG_LABELS[$this->jenjang] ?? '—';
    }
}
