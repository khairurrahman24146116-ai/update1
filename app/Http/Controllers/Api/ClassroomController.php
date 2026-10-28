<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Teacher;
use App\Models\User;
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
        $enabledOnly = $request->boolean('enabled_units');
        $perPage = min(max((int) $request->query('per_page', 15), 1), 100);

        if ($user?->isFinanceRole()) {
            $search = $request->query('search');

            return Classroom::withCount('students')
                ->when($enabledOnly, fn ($q) => $q->inEnabledUnits())
                ->when($yearId, fn ($q) => $q->where('tahun_ajaran_id', $yearId))
                ->when($search, fn ($q) => $q->where(fn ($q2) => $q2->where('name', 'like', "%{$search}%")->orWhere('academic_year', 'like', "%{$search}%")))
                ->orderBy('grade')->orderBy('name')
                ->paginate(100);
        }

        return $this->service->list($request->query('search'), $perPage, $yearId ? (int) $yearId : null, $enabledOnly);
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
            'unit' => 'sometimes|in:lpi,sma,smp',
            'jalur' => 'sometimes|in:formal,lpi',
            'jenjang' => 'sometimes|in:SMP,SMA,Tsanawiyah,Aliyah',
            'tahun_ajaran_id' => 'sometimes|integer|exists:academic_years,id',
            'wali_kelas_id' => 'sometimes|nullable|integer|exists:teachers,id',
        ]);
        if ($unit = $this->unitRestriction($request)) {
            $v['unit'] = $unit;
        }
        $this->ensureWaliKelasAccess($request->user(), $v['unit'] ?? null, $v['wali_kelas_id'] ?? null);
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
            'unit' => 'sometimes|in:lpi,sma,smp',
            'jalur' => 'sometimes|in:formal,lpi',
            'jenjang' => 'sometimes|in:SMP,SMA,Tsanawiyah,Aliyah',
            'tahun_ajaran_id' => 'sometimes|integer|exists:academic_years,id',
            'wali_kelas_id' => 'sometimes|nullable|integer|exists:teachers,id',
        ]);
        if ($unit = $this->unitRestriction($request)) {
            $v['unit'] = $unit;
        }
        if (array_key_exists('wali_kelas_id', $v)) {
            $this->authorize('assignWaliKelas', $c);
            $this->ensureWaliKelasAccess($request->user(), $v['unit'] ?? $c->unit, $v['wali_kelas_id']);
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

    private function ensureWaliKelasAccess(?User $user, ?string $classroomUnit, ?int $teacherId): void
    {
        if ($teacherId === null || $user?->isAdmin()) {
            return;
        }

        $teacher = Teacher::query()->with('units')->find($teacherId);

        if (! $teacher) {
            abort(422, 'Guru wali kelas tidak ditemukan.');
        }

        if ($classroomUnit === null || trim($classroomUnit) === '') {
            abort(422, 'Tentukan unit kelas sebelum memilih wali kelas.');
        }

        $matches = $teacher->units
            ->pluck('kode')
            ->map(fn (string $code): string => strtolower($code))
            ->contains(strtolower($classroomUnit));

        if (! $matches) {
            abort(403, 'Guru wali kelas berada di luar unit kelas ini.');
        }
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

        $codes = $user->effectiveUnitCodes();

        if ($codes === []) {
            return null;
        }

        $requested = strtolower(trim((string) $request->input('unit')));

        if ($requested !== '' && in_array($requested, $codes, true)) {
            return $requested;
        }

        return $request->isMethod('post') ? $codes[0] : null;
    }
}
