<?php

namespace App\Services;

use App\Models\Score;
use App\Models\Student;

class ScoreService
{
    public function list(int $classroomId, int $subjectId, ?string $semester = null, ?string $year = null)
    {
        return Score::with(['student', 'subject', 'classroom'])->where('classroom_id', $classroomId)->where('subject_id', $subjectId)
            ->when($semester, fn ($q) => $q->where('semester', $semester))->when($year, fn ($q) => $q->where('academic_year', $year))->get()->keyBy('student_id');
    }

    public function upsertMany(int $classroomId, int $subjectId, int $teacherId, array $rows, ?string $semester = null, ?string $year = null): void
    {
        if ($rows === []) {
            return;
        }

        $semester ??= 'ganjil';

        if ($year === null) {
            foreach ($rows as $r) {
                Score::updateOrCreate(
                    ['student_id' => $r['student_id'], 'subject_id' => $subjectId, 'semester' => $semester, 'academic_year' => null],
                    ['classroom_id' => $classroomId, 'teacher_id' => $teacherId, 'value' => $r['value']]
                );
            }

            return;
        }

        $values = [];

        foreach ($rows as $r) {
            $values[] = [
                'student_id' => $r['student_id'],
                'subject_id' => $subjectId,
                'semester' => $semester,
                'academic_year' => $year,
                'classroom_id' => $classroomId,
                'teacher_id' => $teacherId,
                'value' => $r['value'],
            ];
        }

        Score::upsert(
            $values,
            ['student_id', 'subject_id', 'semester', 'academic_year'],
            ['classroom_id', 'teacher_id', 'value'],
        );
    }

    public function forWali(int $parentId)
    {
        $ids = Student::where('parent_id', $parentId)->pluck('id');

        return Score::with(['subject', 'classroom', 'student'])->whereIn('student_id', $ids)->orderBy('subject_id')->get()->groupBy('student_id');
    }
}
