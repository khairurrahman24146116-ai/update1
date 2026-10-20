<?php

namespace App\Models;

use Database\Factories\EnrollmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Enrollment extends Model
{
    /** @use HasFactory<EnrollmentFactory> */
    use HasFactory;

    public const STATUS_AKTIF = 'aktif';

    public const STATUS_NAIK = 'naik';

    public const STATUS_TINGGAL = 'tinggal';

    public const STATUS_LULUS = 'lulus';

    public const STATUS_PINDAH = 'pindah';

    public const STATUS_KELUAR = 'keluar';

    public const JALUR_FORMAL = 'formal';

    public const JALUR_LPI = 'lpi';

    protected $fillable = [
        'student_id',
        'class_id',
        'tahun_ajaran_id',
        'jalur',
        'status',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class, 'class_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'tahun_ajaran_id');
    }
}
