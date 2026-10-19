<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Services\ActivityLogger;
use App\Services\ClassroomService;
use Illuminate\Http\Request;

class ClassroomController extends Controller
{
    public function __construct(protected ClassroomService $service) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Classroom::class);

        $user = $request->user();
        if ($user?->isFinanceRole()) {
            $unit = $user->financeUnit();
            $search = $request->query('search');

            return Classroom::withCount('students')
                ->where('unit', $unit)
                ->when($search, fn ($q) => $q->where(fn ($q2) => $q2->where('name', 'like', "%{$search}%")->orWhere('academic_year', 'like', "%{$search}%")))
                ->orderBy('grade')->orderBy('name')
                ->paginate(100);
        }

        return $this->service->list($request->query('search'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', Classroom::class);
        $year = $request->input('academic_year', '2026/2027');
        $v = $request->validate(['name' => 'required|string|max:255', 'grade' => 'required|integer', 'academic_year' => 'sometimes|string|max:20', 'unit' => 'sometimes|in:lpi,sma']);
        if ($unit = $this->unitRestriction($request)) {
            $v['unit'] = $unit;
        }
        $request->validate(['name' => 'unique:classrooms,name,NULL,id,academic_year,'.$year]);
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
        $v = $request->validate(['name' => 'sometimes|string|max:255', 'grade' => 'sometimes|integer', 'academic_year' => 'sometimes|string|max:20', 'unit' => 'sometimes|in:lpi,sma']);
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
