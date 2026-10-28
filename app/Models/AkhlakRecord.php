<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AkhlakRecord extends Model
{
    public const TYPE_PELANGGARAN = 'pelanggaran';

    public const TYPE_APRESIASI = 'apresiasi';

    public const SEVERITY_RINGAN = 'ringan';

    public const SEVERITY_SEDANG = 'sedang';

    public const SEVERITY_BERAT = 'berat';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'student_id',
        'classroom_id',
        'recorded_by',
        'updated_by',
        'type',
        'severity',
        'category',
        'description',
        'points',
        'occurred_at',
        'academic_year',
        'semester',
        'status',
        'internal_note',
        'correction_reason',
        'corrected_at',
    ];

    protected function casts(): array
    {
        return [
            'points' => 'integer',
            'occurred_at' => 'date',
            'corrected_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function isPelanggaran(): bool
    {
        return $this->type === self::TYPE_PELANGGARAN;
    }
}
