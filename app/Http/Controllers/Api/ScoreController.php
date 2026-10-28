<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpsertScoreRequest;
use App\Models\Classroom;
use App\Models\Scopes\BerdasarkanUnit;
use App\Models\Score;
use App\Models\Student;
use App\Models\Subject;
use App\Services\ActivityLogger;
use App\Services\ClassroomService;
use App\Services\ScoreService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ScoreController extends Controller
{
    public function __construct(
        protected ScoreService $service,
        protected ClassroomService $classrooms,
    ) {}

    public function sheet(Request $request)
    {
        $request->validate(['classroom_id' => 'required|exists:classrooms,id', 'subject_id' => 'required|exists:subjects,id', 'semester' => 'nullable|string', 'academic_year' => 'nullable|string']);
        $class = $this->classrooms->find((int) $request->classroom_id);
        $subj = Subject::findOrFail($request->subject_id);
        $this->authorize('viewAny', [Score::class, $class, $subj]);
        $students = Student::query()
            ->when($request->user()->isWaliMurid(), fn ($query) => $query->where('parent_id', $request->user()->id))
            ->when(! $request->user()->isWaliMurid(), fn ($query) => $query->withoutGlobalScope(BerdasarkanUnit::class))
            ->where('classroom_id', $class->id)
            ->where('status', 'aktif')
            ->orderBy('name')
            ->get();
        $scores = $this->service->list($class->id, $subj->id, $request->semester, $request->academic_year);

        return $students->map(fn ($s) => ['student' => $s, 'score' => $scores->get($s->id)]);
    }

    public function store(UpsertScoreRequest $request)
    {
        $class = $this->classrooms->find((int) $request->classroom_id);
        $subj = Subject::findOrFail($request->subject_id);
        $this->authorize('store', [Score::class, $class, $subj]);
        $rows = $request->validated()['rows'];
        $this->ensureStudentsBelongToClassroom($class, $rows);
        $this->service->upsertMany($class->id, $subj->id, $request->user()->id, $rows, $request->input('semester'), $request->input('academic_year'));

        ActivityLogger::log('score.update', $request->user(), Score::class, null, [
            'classroom_id' => $class->id,
            'subject_id' => $subj->id,
            'semester' => $request->input('semester'),
            'academic_year' => $request->input('academic_year'),
            'rows' => count($rows),
        ]);

        return response()->noContent();
    }

    public function waliScores(Request $request)
    {
        if ($request->user()->role !== 'wali_murid') {
            abort(403);
        }

        return $this->service->forWali($request->user()->id);
    }

    /**
     * @param  array<int, array{student_id: int, value: int|float|string}>  $rows
     */
    private function ensureStudentsBelongToClassroom(Classroom $class, array $rows): void
    {
        $studentIds = collect($rows)->pluck('student_id')
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values();

        if ($studentIds->isEmpty()) {
            return;
        }

        $validCount = Student::query()
            ->withoutGlobalScope(BerdasarkanUnit::class)
            ->whereIn('id', $studentIds)
            ->where('classroom_id', $class->id)
            ->count();

        if ($validCount !== $studentIds->count()) {
            throw ValidationException::withMessages([
                'rows' => 'Terdapat siswa yang bukan anggota kelas ini.',
            ]);
        }
    }
}
