<?php

namespace App\Services;

use App\Models\Student;
use App\Models\TeacherSubject;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class StudentService
{
    public function list(array $filters = [], int $perPage = 15, ?User $user = null): LengthAwarePaginator
    {
        $q = Student::query()->with(['classroom', 'parent'])
            ->when($filters['search'] ?? null, fn ($qq, $v) => $qq->where(fn ($q2) => $q2->where('name', 'like', "%{$v}%")->orWhere('nis', 'like', "%{$v}%")))
            ->when($filters['classroom_id'] ?? null, fn ($qq, $v) => $qq->where('classroom_id', $v))
            ->when($filters['status'] ?? null, fn ($qq, $v) => $qq->where('status', $v));

        if ($user) {
            if ($user->role === 'wali_murid') {
                $q->where('parent_id', $user->id);
            }
            if ($user->role === 'guru') {
                $ids = TeacherSubject::where('teacher_id', $user->id)->pluck('classroom_id');
                $q->whereIn('classroom_id', $ids);
            }
            if ($user->isFinanceRole()) {
                $unit = $user->financeUnit();
                $q->whereHas('classroom', fn ($cq) => $cq->where('unit', $unit));
            }
        }

        return $q->orderBy('name')->paginate($perPage);
    }

    public function find(int $id): Student
    {
        return Student::with(['classroom', 'parent'])->findOrFail($id);
    }

    public function create(array $data): Student
    {
        return Student::create($data);
    }

    public function update(Student $student, array $data): Student
    {
        $student->update($data);

        return $student->fresh(['classroom', 'parent']);
    }

    public function delete(Student $student): void
    {
        $student->delete();
    }
}
