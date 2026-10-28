<?php

namespace App\Models\Scopes;

use App\Models\Schedule;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Teacher;
use App\Models\TeacherSubject;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BerdasarkanUnit implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $user = Auth::user();

        if (! $user instanceof User || $user->isAdmin() || $user->isOperator()) {
            return;
        }

        if ($user->isGuru()) {
            $this->applyTeacherScope($builder, $model, $user);

            return;
        }

        if ($user->isWaliMurid()) {
            $this->applyParentScope($builder, $model, $user);

            return;
        }

        $this->applyUnitScope($builder, $model, $user);
    }

    private function applyTeacherScope(Builder $builder, Model $model, User $user): void
    {
        if ($model instanceof Teacher) {
            $builder->where('user_id', $user->id);

            return;
        }

        $classroomIds = TeacherSubject::query()
            ->where('teacher_id', $user->id)
            ->pluck('classroom_id')
            ->merge(
                Schedule::query()
                    ->where('teacher_id', $user->id)
                    ->pluck('classroom_id')
            )
            ->unique()
            ->values()
            ->all();

        if ($classroomIds === []) {
            $builder->whereRaw('1 = 0');

            return;
        }

        if ($model instanceof Student) {
            $builder->whereIn('classroom_id', $classroomIds);

            return;
        }

        if ($model instanceof StudentEnrollment) {
            $builder->whereIn('class_id', $classroomIds);

            return;
        }

        $builder->whereIn('id', $classroomIds);
    }

    private function applyParentScope(Builder $builder, Model $model, User $user): void
    {
        if ($model instanceof Student) {
            $builder->where('parent_id', $user->id);

            return;
        }

        if ($model instanceof StudentEnrollment) {
            $builder->whereHas('student', fn (Builder $query) => $query->where('parent_id', $user->id));

            return;
        }

        if ($model instanceof Teacher) {
            $classroomIds = DB::table('students')
                ->where('parent_id', $user->id)
                ->whereNotNull('classroom_id')
                ->pluck('classroom_id')
                ->all();

            if ($classroomIds === []) {
                $builder->whereRaw('1 = 0');

                return;
            }

            $teacherUserIds = TeacherSubject::query()
                ->whereIn('classroom_id', $classroomIds)
                ->pluck('teacher_id')
                ->merge(
                    Schedule::query()
                        ->whereIn('classroom_id', $classroomIds)
                        ->pluck('teacher_id')
                )
                ->unique()
                ->values()
                ->all();

            if ($teacherUserIds === []) {
                $builder->whereRaw('1 = 0');

                return;
            }

            $builder->whereIn('user_id', $teacherUserIds);

            return;
        }

        $builder->whereHas('students', fn (Builder $query) => $query->where('parent_id', $user->id));
    }

    private function applyUnitScope(Builder $builder, Model $model, User $user): void
    {
        $codes = $user->effectiveUnitCodes();

        if ($codes === []) {
            $builder->whereRaw('1 = 0');

            return;
        }

        $unitIds = Unit::query()
            ->whereIn(DB::raw('LOWER(kode)'), $codes)
            ->pluck('id')
            ->all();

        if ($model instanceof Student) {
            $builder->whereHas('classroom', fn (Builder $query) => $this->whereInUnits($query, $codes, $unitIds));

            return;
        }

        if ($model instanceof StudentEnrollment) {
            $builder->where(function (Builder $query) use ($codes, $unitIds): void {
                if ($unitIds !== []) {
                    $query->whereIn('unit_id', $unitIds)
                        ->orWhereHas('classroom', fn (Builder $classroom) => $this->whereInUnits($classroom, $codes, $unitIds));

                    return;
                }

                $query->whereHas('classroom', fn (Builder $classroom) => $this->whereInUnits($classroom, $codes, $unitIds));
            });

            return;
        }

        if ($model instanceof Teacher) {
            $builder->whereHas('units', fn (Builder $query) => $query->whereIn(DB::raw('LOWER(units.kode)'), $codes));

            return;
        }

        $this->whereInUnits($builder, $codes, $unitIds);
    }

    /**
     * @param  list<string>  $codes
     * @param  list<int>  $unitIds
     */
    private function whereInUnits(Builder $builder, array $codes, array $unitIds): void
    {
        $builder->where(function (Builder $query) use ($codes, $unitIds): void {
            $query->whereIn(DB::raw('LOWER(classrooms.unit)'), $codes);

            if ($unitIds !== []) {
                $query->orWhereIn('classrooms.unit_id', $unitIds);
            }
        });
    }
}
