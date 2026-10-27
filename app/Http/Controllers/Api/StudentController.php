<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\Scopes\BerdasarkanUnit;
use App\Models\Student;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\StudentService;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function __construct(protected StudentService $service) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Student::class);

        return $this->service->list($request->all(), 15, $request->user());
    }

    public function show(int $id, Request $request)
    {
        $student = $this->service->find($id);
        $this->authorize('view', $student);

        return $student;
    }

    public function store(Request $request)
    {
        $this->authorize('create', Student::class);
        $v = $request->validate([
            'nis' => 'required|string|unique:students',
            'nisn' => 'nullable|string|max:20|unique:students,nisn',
            'gender' => 'nullable|in:L,P',
            'name' => 'required|string',
            'classroom_id' => 'required|exists:classrooms,id',
            'parent_id' => ['nullable', 'exists:users,id', function ($attr, $val, $fail) {
                if ($val === null || $val === '') {
                    return;
                }
                $u = User::find($val);
                if (! $u || $u->role !== 'wali_murid') {
                    $fail('Wali murid tidak valid.');
                } elseif (! $u->is_active) {
                    $fail('Wali murid tidak aktif.');
                }
            }],
            'status' => 'in:aktif,nonaktif,lulus',
        ]);
        $this->ensureClassroomAccess($request->user(), (int) $v['classroom_id']);
        $s = $this->service->create($v);
        ActivityLogger::log('student.create', $request->user(), Student::class, $s->id, $v);

        return response()->json($s, 201);
    }

    public function update(Request $request, int $id)
    {
        $s = $this->service->find($id);
        $this->authorize('update', $s);
        $v = $request->validate([
            'nis' => 'sometimes|string|unique:students,nis,'.$id,
            'nisn' => 'sometimes|nullable|string|max:20|unique:students,nisn,'.$id,
            'gender' => 'sometimes|nullable|in:L,P',
            'name' => 'sometimes|string',
            'classroom_id' => 'sometimes|exists:classrooms,id',
            'parent_id' => ['nullable', 'exists:users,id', function ($attr, $val, $fail) {
                if ($val === null || $val === '') {
                    return;
                }
                $u = User::find($val);
                if (! $u || $u->role !== 'wali_murid') {
                    $fail('Wali murid tidak valid.');
                } elseif (! $u->is_active) {
                    $fail('Wali murid tidak aktif.');
                }
            }],
            'status' => 'sometimes|in:aktif,nonaktif,lulus',
        ]);

        if (isset($v['classroom_id'])) {
            $this->ensureClassroomAccess($request->user(), (int) $v['classroom_id']);
        }

        $updated = $this->service->update($s, $v);
        ActivityLogger::log('student.update', $request->user(), Student::class, $id, $v);

        return $updated;
    }

    private function ensureClassroomAccess(?User $user, int $classroomId): void
    {
        if (! $user || $user->isAdmin()) {
            return;
        }

        $classroom = Classroom::withoutGlobalScope(BerdasarkanUnit::class)->find($classroomId);

        if (! $classroom) {
            return;
        }

        if ($user->isGuru()) {
            if (! $user->teachesClassroom($classroom->id)) {
                abort(403, 'Guru hanya dapat mengelola santri pada kelas yang diampu.');
            }

            return;
        }

        if (! $user->canAccessDataUnit($classroom->unit)) {
            abort(403, 'Kelas berada di luar unit akun ini.');
        }
    }

    public function destroy(int $id, Request $request)
    {
        $s = $this->service->find($id);
        $this->authorize('delete', $s);
        $this->service->delete($s);
        ActivityLogger::log('student.delete', $request->user(), Student::class, $id);

        return response()->noContent();
    }
}
