<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpsertScoreRequest;
use App\Models\Classroom;
use App\Models\Score;
use App\Models\Student;
use App\Models\Subject;
use App\Services\ActivityLogger;
use App\Services\ScoreService;
use Illuminate\Http\Request;

class ScoreController extends Controller
{
    public function __construct(protected ScoreService $service) {}

    public function sheet(Request $request)
    {
        $request->validate(['classroom_id' => 'required|exists:classrooms,id', 'subject_id' => 'required|exists:subjects,id', 'semester' => 'nullable|string', 'academic_year' => 'nullable|string']);
        $class = Classroom::findOrFail($request->classroom_id);
        $subj = Subject::findOrFail($request->subject_id);
        $this->authorize('viewAny', [Score::class, $class, $subj]);
        $students = Student::where('classroom_id', $class->id)->where('status', 'aktif')->orderBy('name')->get();
        $scores = $this->service->list($class->id, $subj->id, $request->semester, $request->academic_year);

        return $students->map(fn ($s) => ['student' => $s, 'score' => $scores->get($s->id)]);
    }

    public function store(UpsertScoreRequest $request)
    {
        $class = Classroom::findOrFail($request->classroom_id);
        $subj = Subject::findOrFail($request->subject_id);
        $this->authorize('store', [Score::class, $class, $subj]);
        $rows = $request->validated()['rows'];
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
}
