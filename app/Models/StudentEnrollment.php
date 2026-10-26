<?php

namespace App\Models;

use App\Models\Concerns\ScopedByUnit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentEnrollment extends Model
{
    use ScopedByUnit;

    public const STATUS_AKTIF = 'aktif';

    public const STATUS_NAIK = 'naik';

    public const STATUS_LULUS = 'lulus';

    public const STATUS_KELUAR = 'keluar';

    public const SUMBER_PPDB_SMP = 'ppdb_smp';

    public const SUMBER_PPDB_SMA = 'ppdb_sma';

    public const SUMBER_NAIK_JENJANG = 'naik_jenjang';

    public const SUMBER_MANUAL = 'manual';

    public const JENJANG_SMP = 'SMP';

    public const JENJANG_SMA = 'SMA';

    public const JENJANG_TSANAWIYAH = 'TSANAWIYAH';

    public const JENJANG_ALIYAH = 'ALIYAH';

    protected $table = 'student_enrollments';

    protected $fillable = [
        'student_id',
        'unit_id',
        'jenjang',
        'class_id',
        'tahun_ajaran',
        'status',
        'sumber',
        'alasan_manual',
        'dibuat_oleh',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class, 'class_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }
}
