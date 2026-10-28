<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentWelfareAlert extends Model
{
    public const TYPE_ATTENDANCE = 'attendance_below_min';

    public const TYPE_VIOLATION = 'violation_points';

    public const TYPE_PERMIT_MONTHLY = 'permit_monthly';

    public const TYPE_PERMIT_SEMESTER = 'permit_semester';

    public const TYPE_PERMIT_OVERRIDE = 'permit_override';

    protected $fillable = [
        'student_id',
        'type',
        'period',
        'severity',
        'message',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
