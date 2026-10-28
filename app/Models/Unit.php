<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Unit extends Model
{
    protected $fillable = ['kode', 'nama'];

    /**
     * @return list<string>
     */
    public static function enabledCodes(): array
    {
        $codes = config('units.enabled', ['lpi', 'sma']);

        if (is_string($codes)) {
            $codes = explode(',', $codes);
        }

        return collect(is_array($codes) ? $codes : [])
            ->map(fn ($code): string => strtolower(trim((string) $code)))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public static function isEnabledCode(?string $code): bool
    {
        $code = strtolower(trim((string) $code));

        return $code !== '' && in_array($code, self::enabledCodes(), true);
    }

    /**
     * @return list<int>
     */
    public static function enabledUnitIds(): array
    {
        $codes = self::enabledCodes();

        if ($codes === []) {
            return [];
        }

        return self::query()
            ->whereIn(DB::raw('LOWER(kode)'), $codes)
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }

    public static function isClassroomEnabled(Classroom $classroom): bool
    {
        $code = strtolower(trim((string) $classroom->unit));

        if ($code !== '') {
            return self::isEnabledCode($code);
        }

        return $classroom->unit_id !== null
            && in_array((int) $classroom->unit_id, self::enabledUnitIds(), true);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_unit')
            ->withPivot('jabatan')
            ->withTimestamps();
    }

    public function userUnits(): HasMany
    {
        return $this->hasMany(UserUnit::class);
    }

    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(Teacher::class, 'teacher_unit')
            ->withPivot('jabatan')
            ->withTimestamps();
    }

    public function teacherUnits(): HasMany
    {
        return $this->hasMany(TeacherUnit::class);
    }

    public function classrooms(): HasMany
    {
        return $this->hasMany(Classroom::class);
    }

    public function studentEnrollments(): HasMany
    {
        return $this->hasMany(StudentEnrollment::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }
}
