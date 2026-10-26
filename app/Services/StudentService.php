<?php

namespace App\Services;

use App\Models\Scopes\BerdasarkanUnit;
use App\Models\Student;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class StudentService
{
    public function list(array $filters = [], int $perPage = 15, ?User $user = null): LengthAwarePaginator
    {
        $q = Student::query()->with(['classroom', 'parent'])
            ->when($filters['search'] ?? null, fn ($qq, $v) => $qq->where(fn ($q2) => $q2->where('name', 'like', "%{$v}%")->orWhere('nis', 'like', "%{$v}%")->orWhere('nisn', 'like', "%{$v}%")))
            ->when($filters['classroom_id'] ?? null, fn ($qq, $v) => $qq->where('classroom_id', $v))
            ->when(! empty($filters['without_valid_phone']), fn ($qq) => $qq->withoutValidWaliPhone())
            ->when($filters['status'] ?? null, fn ($qq, $v) => $qq->where('status', $v));

        return $q->orderBy('name')->paginate($perPage);
    }

    public function find(int $id): Student
    {
        return Student::withoutGlobalScope(BerdasarkanUnit::class)
            ->with([
                'classroom' => fn ($query) => $query->withoutGlobalScope(BerdasarkanUnit::class),
                'parent',
            ])
            ->findOrFail($id);
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
