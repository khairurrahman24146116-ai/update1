<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentWelfareSetting extends Model
{
    protected $fillable = [
        'attendance_min_percent',
        'permit_max_per_month',
        'permit_max_per_semester',
        'violation_warning_points',
        'violation_max_points',
        'point_ringan',
        'point_sedang',
        'point_berat',
        'allow_permit_override',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'attendance_min_percent' => 'decimal:2',
            'permit_max_per_month' => 'integer',
            'permit_max_per_semester' => 'integer',
            'violation_warning_points' => 'integer',
            'violation_max_points' => 'integer',
            'point_ringan' => 'integer',
            'point_sedang' => 'integer',
            'point_berat' => 'integer',
            'allow_permit_override' => 'boolean',
        ];
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * @return array<string, int|float|bool>
     */
    public static function defaults(): array
    {
        return [
            'attendance_min_percent' => 75,
            'permit_max_per_month' => 3,
            'permit_max_per_semester' => 12,
            'violation_warning_points' => 20,
            'violation_max_points' => 50,
            'point_ringan' => 5,
            'point_sedang' => 15,
            'point_berat' => 30,
            'allow_permit_override' => true,
        ];
    }

    public function pointForSeverity(string $severity): int
    {
        return match ($severity) {
            AkhlakRecord::SEVERITY_RINGAN => (int) $this->point_ringan,
            AkhlakRecord::SEVERITY_SEDANG => (int) $this->point_sedang,
            AkhlakRecord::SEVERITY_BERAT => (int) $this->point_berat,
            default => 0,
        };
    }
}
