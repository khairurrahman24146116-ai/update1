<?php

namespace App\Services;

use App\Models\Classroom;
use App\Models\Schedule;
use App\Models\Scopes\BerdasarkanUnit;
use App\Models\Subject;
use App\Models\TeacherSubject;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Support\Collection;

class TeacherAssignmentService
{
    public function isClassroomEnabled(Classroom $classroom): bool
    {
        return Unit::isClassroomEnabled($classroom);
    }

    public function canTeach(User $user, Classroom $classroom, ?int $subjectId = null): bool
    {
        if (! $user->isGuru() || ! $this->isClassroomEnabled($classroom)) {
            return false;
        }

        return $this->hasPair($user, $classroom->id, $subjectId);
    }

    public function canAccessSchedule(User $user, Schedule $schedule): bool
    {
        if (! $user->isGuru() || (int) $schedule->teacher_id !== (int) $user->id) {
            return false;
        }

        $schedule->loadMissing('classroom');

        return $schedule->classroom !== null
            && $this->isClassroomEnabled($schedule->classroom);
    }

    /**
     * @return Collection<int, array{classroom_id: int, classroom_name: string, classroom_unit: string, classroom_jenjang: string, classroom_label: string, subject_id: int, subject_name: string, subject_code: string|null, subject_jenis: string, subject_is_active: bool, academic_year: string|null}>
     */
    public function assignmentsFor(User $user): Collection
    {
        $classroomIds = Classroom::query()
            ->withoutGlobalScope(BerdasarkanUnit::class)
            ->inEnabledUnits()
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        if ($classroomIds === []) {
            return collect();
        }

        $relations = [
            'classroom' => fn ($query) => $query->withoutGlobalScope(BerdasarkanUnit::class),
            'subject',
        ];

        $fromSubjects = TeacherSubject::query()
            ->with($relations)
            ->where('teacher_id', $user->id)
            ->whereIn('classroom_id', $classroomIds)
            ->orderByDesc('academic_year')
            ->get()
            ->map(fn (TeacherSubject $assignment): array => $this->payload(
                $assignment->classroom,
                $assignment->subject,
                $assignment->academic_year,
            ));

        $fromSchedules = Schedule::query()
            ->with($relations)
            ->where('teacher_id', $user->id)
            ->whereIn('classroom_id', $classroomIds)
            ->get()
            ->map(fn (Schedule $schedule): array => $this->payload(
                $schedule->classroom,
                $schedule->subject,
                $schedule->classroom?->academic_year,
            ));

        return $fromSubjects
            ->merge($fromSchedules)
            ->filter(fn (array $assignment): bool => $assignment['classroom_id'] !== null && $assignment['subject_id'] !== null)
            ->unique(fn (array $assignment): string => $assignment['classroom_id'].':'.$assignment['subject_id'])
            ->sortBy(fn (array $assignment): string => strtolower($assignment['classroom_name'].'|'.$assignment['subject_name']))
            ->values();
    }

    public function hasClassroom(User $user, int $classroomId): bool
    {
        return $this->hasPair($user, $classroomId);
    }

    private function hasPair(User $user, int $classroomId, ?int $subjectId = null): bool
    {
        $subjectFilter = fn ($query) => $subjectId === null
            ? $query
            : $query->where('subject_id', $subjectId);

        if (TeacherSubject::query()
            ->where('teacher_id', $user->id)
            ->where('classroom_id', $classroomId)
            ->where($subjectFilter)
            ->exists()) {
            return true;
        }

        return Schedule::query()
            ->where('teacher_id', $user->id)
            ->where('classroom_id', $classroomId)
            ->where($subjectFilter)
            ->exists();
    }

    /**
     * @return array{classroom_id: int|null, classroom_name: string, classroom_unit: string, classroom_jenjang: string, classroom_label: string, subject_id: int|null, subject_name: string, subject_code: string|null, subject_jenis: string, subject_is_active: bool, academic_year: string|null}
     */
    private function payload(?Classroom $classroom, ?Subject $subject, ?string $academicYear): array
    {
        return [
            'classroom_id' => $classroom?->id,
            'classroom_name' => (string) $classroom?->name,
            'classroom_unit' => strtoupper((string) $classroom?->unit),
            'classroom_jenjang' => (string) $classroom?->jenjang,
            'classroom_label' => $classroom?->unitLabel() ?? '-',
            'subject_id' => $subject?->id,
            'subject_name' => (string) $subject?->name,
            'subject_code' => $subject?->code,
            'subject_jenis' => (string) ($subject?->jenis ?? Subject::JENIS_FORMAL),
            'subject_is_active' => (bool) ($subject?->is_active ?? false),
            'academic_year' => $academicYear,
        ];
    }
}
