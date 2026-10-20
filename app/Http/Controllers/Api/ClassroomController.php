<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Services\ActivityLogger;
use App\Services\ClassroomService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClassroomController extends Controller
{
    public function __construct(protected ClassroomService $service) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Classroom::class);

        $user = $request->user();
        $yearId = $request->query('tahun_ajaran_id');

        if ($user?->isFinanceRole()) {
            $unit = $user->financeUnit();
            $search = $request->query('search');

            return Classroom::withCount('students')
                ->where('unit', $unit)
                ->when($yearId, fn ($q) => $q->where('tahun_ajaran_id', $yearId))
                ->when($search, fn ($q) => $q->where(fn ($q2) => $q2->where('name', 'like', "%{$search}%")->orWhere('academic_year', 'like', "%{$search}%")))
                ->orderBy('grade')->orderBy('name')
                ->paginate(100);
        }

        return $this->service->list($request->query('search'), 15, $yearId ? (int) $yearId : null);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Classroom::class);
        $year = $request->input('academic_year');

        if (! $year && $request->filled('tahun_ajaran_id')) {
            $year = AcademicYear::find($request->input('tahun_ajaran_id'))?->name;
        }

        $year ??= '2026/2027';
        $v = $request->validate([
            'name' => 'required|string|max:255',
            'grade' => 'required|integer|between:7,12',
            'academic_year' => 'sometimes|string|max:20',
            'unit' => 'sometimes|in:lpi,sma',
            'jalur' => 'sometimes|in:formal,lpi',
            'jenjang' => 'sometimes|in:SMP,SMA,Tsanawiyah,Aliyah',
            'tahun_ajaran_id' => 'sometimes|integer|exists:academic_years,id',
        ]);
        if ($unit = $this->unitRestriction($request)) {
            $v['unit'] = $unit;
        }
        $request->validate([
            'name' => [Rule::unique('classrooms', 'name')->where('academic_year', $year)],
        ]);
        $c = $this->service->create($v);
        ActivityLogger::log('classroom.create', $request->user(), Classroom::class, $c->id, $v);

        return response()->json($c, 201);
    }

    public function show(int $id)
    {
        $c = $this->service->find($id);
        $this->authorize('view', $c);

        return $c;
    }

    public function update(Request $request, int $id)
    {
        $c = $this->service->find($id);
        $this->authorize('update', $c);
        $v = $request->validate([
            'name' => 'sometimes|string|max:255',
            'grade' => 'sometimes|integer|between:7,12',
            'academic_year' => 'sometimes|string|max:20',
            'unit' => 'sometimes|in:lpi,sma',
            'jalur' => 'sometimes|in:formal,lpi',
            'jenjang' => 'sometimes|in:SMP,SMA,Tsanawiyah,Aliyah',
            'tahun_ajaran_id' => 'sometimes|integer|exists:academic_years,id',
        ]);
        if ($unit = $this->unitRestriction($request)) {
            $v['unit'] = $unit;
        }
        $updated = $this->service->update($c, $v);
        ActivityLogger::log('classroom.update', $request->user(), Classroom::class, $id, $v);

        return $updated;
    }

    public function destroy(int $id, Request $request)
    {
        $c = $this->service->find($id);
        $this->authorize('delete', $c);
        $this->service->delete($c);
        ActivityLogger::log('classroom.delete', $request->user(), Classroom::class, $id);

        return response()->noContent();
    }

    /**
     * Akun non-admin yang memiliki unit hanya boleh membuat/mengubah kelas
     * pada unitnya sendiri.
     */
    private function unitRestriction(Request $request): ?string
    {
        $user = $request->user();

        if (! $user || $user->isAdmin()) {
            return null;
        }

        return $user->financeUnit() ?? $user->unit;
    }
}
