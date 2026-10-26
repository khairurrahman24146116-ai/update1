<?php

namespace App\Models;

use App\Models\Concerns\ScopedByUnit;
use Database\Factories\ClassroomFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function isFormalSmaGradeTen(): bool
    {
        return $this->jalur === self::JALUR_FORMAL
            && $this->jenjang === self::JENJANG_SMA
            && (int) $this->grade === 10;
    }
}
