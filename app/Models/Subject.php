<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subject extends Model
{
    public const JENIS_FORMAL = 'formal';

    public const JENIS_NON_FORMAL = 'non_formal';

    protected $fillable = ['name', 'code', 'jenis', 'is_active'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function teacherSubjects(): HasMany
    {
        return $this->hasMany(TeacherSubject::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    public function scores(): HasMany
    {
        return $this->hasMany(Score::class);
    }

    public function scopes(): HasMany
    {
        return $this->hasMany(SubjectScope::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForPlacement(Builder $query, string $unit, string $jenjang): Builder
    {
        return $query->whereHas('scopes', fn (Builder $scopeQuery) => $scopeQuery
            ->whereRaw('LOWER(unit) = ?', [strtolower($unit)])
            ->whereRaw('LOWER(jenjang) = ?', [strtolower($jenjang)]));
    }

    public function isAvailableFor(Classroom $classroom): bool
    {
        $unit = strtolower(trim((string) $classroom->unit));
        $jenjang = strtolower(trim((string) $classroom->jenjang));

        if ($unit === '' || $jenjang === '') {
            return false;
        }

        $scopes = $this->relationLoaded('scopes')
            ? $this->scopes
            : $this->scopes()->get();

        if ($scopes->isEmpty()) {
            return true;
        }

        return $scopes->contains(fn (SubjectScope $scope): bool => strtolower($scope->unit) === $unit
            && strtolower($scope->jenjang) === $jenjang);
    }

    public function hasUsage(): bool
    {
        return array_sum($this->usageSummary()) > 0;
    }

    /**
     * @return array{assignments: int, schedules: int, scores: int}
     */
    public function usageSummary(): array
    {
        return [
            'assignments' => $this->teacherSubjects()->count(),
            'schedules' => $this->schedules()->count(),
            'scores' => $this->scores()->count(),
        ];
    }
}
