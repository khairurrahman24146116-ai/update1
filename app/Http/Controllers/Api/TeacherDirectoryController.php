<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherAttendance;
use App\Models\TeacherSubject;
use App\Models\Unit;
use App\Models\User;
use App\Services\TeacherAttendanceService;
use App\Services\TeacherProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TeacherDirectoryController extends Controller
{
    public function __construct(
        protected TeacherProfileService $profiles,
        protected TeacherAttendanceService $attendance,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Teacher::class);

        /** @var User $actor */
        $actor = $request->user();
        $unit = strtolower(trim((string) $request->query('unit', 'all')));
        $status = (string) $request->query('status', '');
        $search = trim((string) $request->query('search', ''));
        $perPage = min(max((int) $request->query('per_page', 20), 1), 100);

        if ($unit !== 'all' && ! Unit::isEnabledCode($unit)) {
            abort(403, 'Unit tidak aktif.');
        }

        if ($actor->isKepalaSekolah() && $unit !== 'all' && ! in_array($unit, $actor->effectiveUnitCodes(), true)) {
            abort(403, 'Unit di luar kewenangan Anda.');
        }

        $teachers = Teacher::query()
            ->whereHas('user', fn ($query) => $query->whereIn('role', [User::ROLE_GURU, User::ROLE_PENDING]))
            ->with([
                'user:id,name,email,phone,role,unit,is_active,created_at',
                'units:id,kode,nama',
                'teacherSubjects:id,teacher_id,classroom_id',
                'classrooms:id,wali_kelas_id,unit',
            ])
            ->when($unit !== 'all', fn ($query) => $query->whereHas(
                'units',
                fn ($unitQuery) => $unitQuery->whereRaw('LOWER(units.kode) = ?', [$unit]),
            ))
            ->when($search !== '', fn ($query) => $query->where(function ($searchQuery) use ($search): void {
                $searchQuery->where('nama', 'like', "%{$search}%")
                    ->orWhere('nip', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($userQuery) => $userQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%"));
            }))
            ->when($status === 'pending', fn ($query) => $query->whereHas('user', fn ($userQuery) => $userQuery->where('role', User::ROLE_PENDING)))
            ->when($status === 'aktif', fn ($query) => $query->whereHas('user', fn ($userQuery) => $userQuery
                ->where('role', User::ROLE_GURU)
                ->where('is_active', true)))
            ->when($status === 'nonaktif', fn ($query) => $query->whereHas('user', fn ($userQuery) => $userQuery
                ->where('role', User::ROLE_GURU)
                ->where('is_active', false)))
            ->orderBy('nama')
            ->paginate($perPage);

        $payload = $teachers->toArray();
        $payload['data'] = collect($teachers->items())
            ->map(fn (Teacher $teacher): array => $this->listPayload($teacher))
            ->all();
        $payload['tabs'] = $this->tabs($actor);
        $payload['permissions'] = [
            'create' => Gate::allows('create', Teacher::class),
        ];

        return response()->json($payload);
    }

    public function options(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Teacher::class);

        /** @var User $actor */
        $actor = $request->user();
        $enabledCodes = Unit::enabledCodes();
        $unitCodes = $actor->isAdmin() || $actor->isOperator()
            ? Unit::query()->orderBy('kode')->pluck('kode')->map(fn (string $code): string => strtolower($code))->all()
            : $actor->effectiveUnitCodes();
        $unitCodes = array_values(array_intersect($unitCodes, $enabledCodes));

        $units = Unit::query()
            ->whereIn(DB::raw('LOWER(kode)'), $unitCodes)
            ->orderBy('kode')
            ->get(['id', 'kode', 'nama'])
            ->map(fn (Unit $unit): array => [
                'code' => strtoupper($unit->kode),
                'name' => $unit->nama,
            ]);

        $unitNames = Unit::query()->pluck('nama', 'kode')
            ->mapWithKeys(fn (string $name, string $code): array => [strtoupper($code) => $name]);

        $classrooms = Classroom::query()
            ->inEnabledUnits()
            ->orderBy('grade')
            ->orderBy('name')
            ->get()
            ->map(fn (Classroom $classroom): array => [
                'id' => $classroom->id,
                'name' => $classroom->name,
                'grade' => $classroom->grade,
                'unit' => strtoupper((string) $classroom->unit),
                'jenjang' => (string) $classroom->jenjang,
                'unit_name' => $unitNames->get(strtoupper((string) $classroom->unit)),
                'label' => $classroom->unitLabel(),
                'academic_year' => $classroom->academic_year,
            ]);

        $activeYear = AcademicYear::query()->where('is_active', true)->value('name') ?? '2026/2027';

        return response()->json([
            'units' => $units,
            'subjects' => Subject::query()
                ->with('scopes')
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'code', 'jenis']),
            'classrooms' => $classrooms,
            'academic_year' => $activeYear,
        ]);
    }

    public function show(Teacher $teacher): JsonResponse
    {
        $this->authorize('view', $teacher);

        return response()->json($this->detailPayload($teacher));
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Teacher::class);

        /** @var User $actor */
        $actor = $request->user();

        if ($actor->isOperator() && ($request->exists('role') || $request->exists('units'))) {
            throw ValidationException::withMessages([
                'role' => 'Operator tidak dapat menetapkan role atau unit guru.',
            ]);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'phone' => ['nullable', 'string', 'max:30'],
            'nip' => ['nullable', 'string', 'max:30', 'unique:teachers,nip'],
            'is_active' => ['sometimes', 'boolean'],
            'units' => ['sometimes', 'array'],
            'units.*.code' => ['required', 'string', 'exists:units,kode'],
            'units.*.jabatan' => ['nullable', 'string', 'max:100'],
        ]);

        $role = $actor->isAdminUtama() ? User::ROLE_GURU : User::ROLE_PENDING;

        $teacher = DB::transaction(function () use ($actor, $data, $role): Teacher {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'password' => Hash::make($data['password']),
                'role' => $role,
                'unit' => null,
                'is_active' => $data['is_active'] ?? true,
            ]);

            $teacher = $this->profiles->ensureForUser($user);
            $teacher->update(['nip' => $data['nip'] ?? null]);

            if ($actor->isAdminUtama() && array_key_exists('units', $data)) {
                $teacher = $this->profiles->updateUnits($teacher, $data['units']);
            } else {
                $teacher = $this->profiles->syncFromUser($user);
            }

            activity_log('user.create', $actor, User::class, $user->id, [
                'role' => $role,
                'source' => 'teacher-directory',
            ]);
            activity_log('teacher.create', $actor, Teacher::class, $teacher->id, [
                'user_id' => $user->id,
                'units' => $data['units'] ?? [],
            ]);

            return $teacher;
        });

        return response()->json($this->detailPayload($teacher->fresh()), 201);
    }

    public function update(Request $request, Teacher $teacher): JsonResponse
    {
        $this->authorize('update', $teacher);

        /** @var User $actor */
        $actor = $request->user();
        $user = $teacher->user;

        if ($user === null) {
            abort(422, 'Profil guru belum terhubung dengan akun pengguna.');
        }

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'nip' => ['sometimes', 'nullable', 'string', 'max:30', Rule::unique('teachers', 'nip')->ignore($teacher->id)],
            'is_active' => ['sometimes', 'boolean'],
            'password' => ['sometimes', 'nullable', 'string', 'min:6'],
        ]);

        $passwordChanged = filled($data['password'] ?? null);

        DB::transaction(function () use ($actor, $data, $passwordChanged, $teacher, $user): void {
            $user->fill([
                'name' => $data['name'] ?? $user->name,
                'email' => $data['email'] ?? $user->email,
                'phone' => array_key_exists('phone', $data) ? $data['phone'] : $user->phone,
                'is_active' => $data['is_active'] ?? $user->is_active,
            ]);

            if ($passwordChanged) {
                $user->password = Hash::make($data['password']);
            }

            $user->save();

            $teacher->update([
                'nama' => $data['name'] ?? $user->name,
                'nip' => array_key_exists('nip', $data) ? $data['nip'] : $teacher->nip,
            ]);

            activity_log('teacher.update', $actor, Teacher::class, $teacher->id, [
                'user_id' => $user->id,
                'password_changed' => $passwordChanged,
            ]);
        });

        return response()->json($this->detailPayload($teacher->fresh()));
    }

    public function uploadPhoto(Request $request, Teacher $teacher): JsonResponse
    {
        $this->authorize('update', $teacher);

        $request->validate([
            'file' => ['required', 'file', 'max:5120'],
        ]);

        $file = $request->file('file');
        $mime = $file->getMimeType();
        $allowed = ['image/png', 'image/jpeg', 'image/webp'];

        if (! in_array($mime, $allowed, true)) {
            throw ValidationException::withMessages([
                'file' => 'Tipe file tidak diizinkan. Gunakan PNG, JPG, atau WebP.',
            ]);
        }

        $extension = match ($mime) {
            'image/png' => 'png',
            'image/jpeg' => 'jpg',
            'image/webp' => 'webp',
            default => 'bin',
        };

        $oldPath = $teacher->photo_path;
        $filename = 'teachers/'.Str::random(32).'.'.$extension;
        $file->storeAs('', $filename, 'public');
        $teacher->update(['photo_path' => $filename]);

        if ($oldPath && ! Str::startsWith($oldPath, '/')) {
            Storage::disk('public')->delete($oldPath);
        }

        activity_log('teacher.photo.upload', $request->user(), Teacher::class, $teacher->id, [
            'path' => $filename,
        ]);

        return response()->json($this->detailPayload($teacher->fresh()));
    }

    public function updateUnits(Request $request, Teacher $teacher): JsonResponse
    {
        $this->authorize('manageUnits', $teacher);

        $data = $request->validate([
            'units' => ['present', 'array'],
            'units.*.code' => ['required', 'string', 'exists:units,kode'],
            'units.*.jabatan' => ['nullable', 'string', 'max:100'],
        ]);

        $old = $teacher->units->map(fn (Unit $unit): array => [
            'code' => strtoupper($unit->kode),
            'jabatan' => $unit->pivot->jabatan,
        ])->all();

        $teacher = DB::transaction(fn (): Teacher => $this->profiles->updateUnits($teacher, $data['units']));

        activity_log('teacher.units.update', $request->user(), Teacher::class, $teacher->id, [
            'old' => $old,
            'new' => $data['units'],
        ]);

        return response()->json($this->detailPayload($teacher));
    }

    public function updateAssignments(Request $request, Teacher $teacher): JsonResponse
    {
        $this->authorize('manageAssignments', $teacher);

        $data = $request->validate([
            'academic_year' => ['sometimes', 'string', 'max:20'],
            'assignments' => ['present', 'array'],
            'assignments.*.subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'assignments.*.classroom_id' => ['required', 'integer', 'exists:classrooms,id'],
            'wali_classroom_ids' => ['present', 'array'],
            'wali_classroom_ids.*' => ['required', 'integer', 'exists:classrooms,id'],
        ]);

        $user = $teacher->user;

        if ($user === null) {
            abort(422, 'Profil guru belum terhubung dengan akun pengguna.');
        }

        $academicYear = $data['academic_year'] ?? '2026/2027';
        $teacherUnitCodes = $teacher->units()->pluck('units.kode')
            ->map(fn (string $code): string => strtolower($code))
            ->all();

        $classroomIds = collect($data['assignments'])->pluck('classroom_id')
            ->merge($data['wali_classroom_ids'])
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values();

        $classrooms = Classroom::query()
            ->whereIn('id', $classroomIds)
            ->get()
            ->keyBy('id');

        if ($classrooms->count() !== $classroomIds->count()) {
            abort(403, 'Terdapat kelas di luar kewenangan Anda.');
        }

        foreach ($classrooms as $classroom) {
            if (! Unit::isClassroomEnabled($classroom)) {
                abort(403, "Kelas {$classroom->name} tidak aktif.");
            }

            if (! in_array(strtolower((string) $classroom->unit), $teacherUnitCodes, true)) {
                throw ValidationException::withMessages([
                    'assignments' => "Kelas {$classroom->name} berada di luar unit guru.",
                ]);
            }
        }

        $subjects = Subject::query()
            ->with('scopes')
            ->whereIn('id', collect($data['assignments'])->pluck('subject_id')->unique())
            ->get()
            ->keyBy('id');

        foreach ($data['assignments'] as $assignment) {
            $subject = $subjects->get((int) $assignment['subject_id']);
            $classroom = $classrooms->get((int) $assignment['classroom_id']);

            if ($subject === null || ! $subject->is_active) {
                throw ValidationException::withMessages([
                    'assignments' => 'Mapel yang dipilih tidak aktif.',
                ]);
            }

            if ($classroom !== null && ! $subject->isAvailableFor($classroom)) {
                throw ValidationException::withMessages([
                    'assignments' => "Mapel {$subject->name} tidak sesuai unit/jenjang kelas {$classroom->name}.",
                ]);
            }
        }

        $assignments = [];
        $seenAssignments = [];

        foreach ($data['assignments'] as $assignment) {
            $key = $assignment['subject_id'].':'.$assignment['classroom_id'];

            if (isset($seenAssignments[$key])) {
                continue;
            }

            $seenAssignments[$key] = true;
            $assignments[] = [
                'teacher_id' => $user->id,
                'subject_id' => $assignment['subject_id'],
                'classroom_id' => $assignment['classroom_id'],
                'academic_year' => $academicYear,
            ];
        }

        $old = [
            'assignments' => TeacherSubject::query()
                ->where('teacher_id', $user->id)
                ->where('academic_year', $academicYear)
                ->get(['subject_id', 'classroom_id'])
                ->toArray(),
            'wali_classroom_ids' => Classroom::query()
                ->where('wali_kelas_id', $teacher->id)
                ->pluck('id')
                ->all(),
        ];

        DB::transaction(function () use ($assignments, $academicYear, $data, $teacher, $user): void {
            TeacherSubject::query()
                ->where('teacher_id', $user->id)
                ->where('academic_year', $academicYear)
                ->delete();

            if ($assignments !== []) {
                TeacherSubject::query()->insert($assignments);
            }

            Classroom::query()
                ->where('wali_kelas_id', $teacher->id)
                ->update(['wali_kelas_id' => null]);

            if ($data['wali_classroom_ids'] !== []) {
                Classroom::query()
                    ->whereIn('id', $data['wali_classroom_ids'])
                    ->update(['wali_kelas_id' => $teacher->id]);
            }
        });

        activity_log('teacher.assignments.update', $request->user(), Teacher::class, $teacher->id, [
            'academic_year' => $academicYear,
            'old' => $old,
            'new' => [
                'assignments' => $assignments,
                'wali_classroom_ids' => $data['wali_classroom_ids'],
            ],
        ]);

        return response()->json($this->detailPayload($teacher->fresh()));
    }

    public function attendance(Request $request, Teacher $teacher): JsonResponse
    {
        $this->authorize('view', $teacher);

        $month = (string) $request->query('month', now()->format('Y-m'));

        if (! preg_match('/^\d{4}-\d{2}$/', $month)) {
            throw ValidationException::withMessages([
                'month' => 'Format bulan harus YYYY-MM.',
            ]);
        }

        $start = $month.'-01';
        $end = date('Y-m-t', strtotime($start));

        $records = TeacherAttendance::query()
            ->where('teacher_id', $teacher->user_id)
            ->whereBetween('date', [$start, $end])
            ->orderBy('date')
            ->get();

        return response()->json([
            'month' => $month,
            'records' => $records->map(fn (TeacherAttendance $record): array => [
                'id' => $record->id,
                'date' => $record->date?->format('Y-m-d'),
                'check_in' => $record->check_in,
                'check_out' => $record->check_out,
                'status' => $record->status,
                'notes' => $record->notes,
            ])->values(),
            'summary' => [
                'total' => $records->count(),
                'hadir' => $records->where('status', 'hadir')->count(),
                'izin' => $records->where('status', 'izin')->count(),
                'sakit' => $records->where('status', 'sakit')->count(),
                'alfa' => $records->where('status', 'alfa')->count(),
            ],
        ]);
    }

    public function updateAttendance(Request $request, Teacher $teacher, TeacherAttendance $attendance): JsonResponse
    {
        $this->authorize('manageAttendance', $teacher);

        if ((int) $attendance->teacher_id !== (int) $teacher->user_id) {
            abort(404);
        }

        $data = $request->validate([
            'date' => ['sometimes', 'date_format:Y-m-d'],
            'check_in' => ['sometimes', 'nullable', 'date_format:H:i'],
            'check_out' => ['sometimes', 'nullable', 'date_format:H:i'],
            'status' => ['sometimes', Rule::in(['hadir', 'izin', 'sakit', 'alfa', 'cuti'])],
            'notes' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ]);

        if (array_key_exists('date', $data) && $data['date'] !== $attendance->date?->format('Y-m-d')) {
            $duplicate = TeacherAttendance::query()
                ->where('teacher_id', $teacher->user_id)
                ->where('date', $data['date'])
                ->where('id', '!=', $attendance->id)
                ->exists();

            if ($duplicate) {
                throw ValidationException::withMessages([
                    'date' => 'Absensi guru pada tanggal tersebut sudah ada.',
                ]);
            }
        }

        foreach (['check_in', 'check_out'] as $field) {
            if (array_key_exists($field, $data) && filled($data[$field])) {
                $data[$field] = $data[$field].':00';
            }
        }

        $record = $this->attendance->update($attendance, $data);

        activity_log('attendance.teacher.update', $request->user(), TeacherAttendance::class, $record->id, [
            'teacher_id' => $teacher->id,
            'date' => $record->date,
            'status' => $record->status,
        ]);

        return response()->json($record);
    }

    /**
     * @return array<string, mixed>
     */
    private function listPayload(Teacher $teacher): array
    {
        $user = $teacher->user;
        $classIds = $teacher->teacherSubjects->pluck('classroom_id')
            ->merge($teacher->classrooms->pluck('id'))
            ->unique();

        return [
            'id' => $teacher->id,
            'user_id' => $teacher->user_id,
            'name' => $user?->name ?? $teacher->nama,
            'email' => $user?->email,
            'phone' => $user?->phone,
            'nip' => $teacher->nip,
            'photo_path' => $teacher->photo_path,
            'photo_url' => $this->photoUrl($teacher->photo_path),
            'role' => $user?->role,
            'is_active' => (bool) $user?->is_active,
            'status' => $this->status($user),
            'units' => $teacher->units->map(fn (Unit $unit): array => [
                'code' => strtoupper($unit->kode),
                'name' => $unit->nama,
                'jabatan' => $unit->pivot->jabatan,
            ])->values()->all(),
            'class_count' => $classIds->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function detailPayload(Teacher $teacher): array
    {
        $teacher->load([
            'user:id,name,email,phone,role,unit,is_active,created_at',
            'units:id,kode,nama',
            'classrooms:id,name,grade,unit,jenjang,academic_year,wali_kelas_id',
            'teacherSubjects.subject:id,name,code,jenis',
            'teacherSubjects.classroom:id,name,grade,unit,jenjang,academic_year',
        ]);

        $user = $teacher->user;
        $classIds = $teacher->teacherSubjects->pluck('classroom_id')
            ->merge($teacher->classrooms->pluck('id'))
            ->unique();

        return [
            'id' => $teacher->id,
            'user_id' => $teacher->user_id,
            'name' => $user?->name ?? $teacher->nama,
            'email' => $user?->email,
            'phone' => $user?->phone,
            'nip' => $teacher->nip,
            'photo_path' => $teacher->photo_path,
            'photo_url' => $this->photoUrl($teacher->photo_path),
            'role' => $user?->role,
            'is_active' => (bool) $user?->is_active,
            'status' => $this->status($user),
            'created_at' => $user?->created_at,
            'units' => $teacher->units->map(fn (Unit $unit): array => [
                'code' => strtoupper($unit->kode),
                'name' => $unit->nama,
                'jabatan' => $unit->pivot->jabatan,
            ])->values()->all(),
            'class_count' => $classIds->count(),
            'wali_classrooms' => $teacher->classrooms->map(fn (Classroom $classroom): array => [
                'id' => $classroom->id,
                'name' => $classroom->name,
                'grade' => $classroom->grade,
                'unit' => strtoupper((string) $classroom->unit),
                'jenjang' => (string) $classroom->jenjang,
                'label' => $classroom->unitLabel(),
                'academic_year' => $classroom->academic_year,
            ])->values()->all(),
            'assignments' => $teacher->teacherSubjects
                ->filter(fn (TeacherSubject $assignment): bool => $assignment->classroom !== null)
                ->map(fn (TeacherSubject $assignment): array => [
                    'id' => $assignment->id,
                    'subject_id' => $assignment->subject_id,
                    'subject_name' => $assignment->subject?->name,
                    'subject_jenis' => $assignment->subject?->jenis,
                    'classroom_id' => $assignment->classroom_id,
                    'classroom_name' => $assignment->classroom?->name,
                    'classroom_unit' => strtoupper((string) $assignment->classroom?->unit),
                    'classroom_jenjang' => (string) $assignment->classroom?->jenjang,
                    'classroom_label' => $assignment->classroom?->unitLabel(),
                    'academic_year' => $assignment->academic_year,
                ])->values()->all(),
            'permissions' => [
                'update' => Gate::allows('update', $teacher),
                'manage_units' => Gate::allows('manageUnits', $teacher),
                'manage_assignments' => Gate::allows('manageAssignments', $teacher),
                'manage_attendance' => Gate::allows('manageAttendance', $teacher),
            ],
        ];
    }

    /**
     * @return array<int, array{code: string, label: string}>
     */
    private function tabs(User $actor): array
    {
        $query = Unit::query()
            ->whereIn(DB::raw('LOWER(kode)'), Unit::enabledCodes())
            ->orderBy('kode');

        if ($actor->isKepalaSekolah()) {
            $query->whereIn(DB::raw('LOWER(kode)'), $actor->effectiveUnitCodes());
        }

        $tabs = $query->get(['kode', 'nama'])
            ->map(fn (Unit $unit): array => [
                'code' => strtoupper($unit->kode),
                'label' => $unit->nama,
            ])
            ->values()
            ->all();

        if (! $actor->isKepalaSekolah()) {
            array_unshift($tabs, ['code' => 'all', 'label' => 'Semua']);
        }

        return $tabs;
    }

    private function status(?User $user): string
    {
        if ($user?->isPending()) {
            return 'pending';
        }

        return $user?->is_active ? 'aktif' : 'nonaktif';
    }

    private function photoUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://', '/'])) {
            return $path;
        }

        return '/storage/'.ltrim($path, '/');
    }
}
