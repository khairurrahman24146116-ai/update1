<?php

namespace App\Models;

use App\Models\Concerns\ScopedByUnit;
use Database\Factories\ClassroomFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Classroom extends Model
{
    /** @use HasFactory<ClassroomFactory> */
    use HasFactory, ScopedByUnit;

    public const JALUR_FORMAL = 'formal';

    public const JALUR_LPI = 'lpi';

    public const JENJANG_SMP = 'SMP';

    public const JENJANG_SMA = 'SMA';

    public const JENJANG_TSANAWIYAH = 'Tsanawiyah';

    public const JENJANG_ALIYAH = 'Aliyah';

    protected $fillable = [
        'name',
        'grade',
        'academic_year',
        'unit',
        'unit_id',
        'wali_kelas_id',
        'jalur',
        'jenjang',
        'tahun_ajaran_id',
    ];

    protected function casts(): array
    {
        return [
            'grade' => 'integer',
            'unit_id' => 'integer',
            'wali_kelas_id' => 'integer',
            'tahun_ajaran_id' => 'integer',
        ];
    }

    public function scopeInEnabledUnits(Builder $query): Builder
    {
        $codes = Unit::enabledCodes();
        $unitIds = Unit::enabledUnitIds();

        return $query->where(function (Builder $query) use ($codes, $unitIds): void {
            if ($codes === [] && $unitIds === []) {
                $query->whereRaw('1 = 0');

                return;
            }

            if ($codes !== []) {
                $query->whereIn(DB::raw('LOWER(classrooms.unit)'), $codes);
            }

            if ($unitIds !== []) {
                $fallback = function (Builder $query) use ($unitIds): void {
                    $query->where(function (Builder $query): void {
                        $query->whereNull('unit')->orWhere('unit', '');
                    })->whereIn('unit_id', $unitIds);
                };

                if ($codes !== []) {
                    $query->orWhere($fallback);
                } else {
                    $query->where($fallback);
                }
            }
        });
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function waliKelas(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'wali_kelas_id');
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function teacherSubjects(): HasMany
    {
        return $this->hasMany(TeacherSubject::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'tahun_ajaran_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class, 'class_id');
    }

    public function studentEnrollments(): HasMany
    {
        return $this->hasMany(StudentEnrollment::class, 'class_id');
    }

    public function unitLabel(): string
    {
        $unit = strtoupper(trim((string) $this->unit));
        $jenjang = trim((string) $this->jenjang);

        if ($unit === '') {
            return $jenjang !== '' ? $jenjang : '-';
        }

        if ($jenjang === '' || strcasecmp($unit, $jenjang) === 0) {
            return $unit;
        }

        return "{$unit} - {$jenjang}";
    }

    public function isFormalSmaGradeTen(): bool
    {
        return $this->jalur === self::JALUR_FORMAL
            && $this->jenjang === self::JENJANG_SMA
            && (int) $this->grade === 10;
    }
}
