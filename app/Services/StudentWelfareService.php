<?php

namespace App\Services;

use App\Models\AkhlakRecord;
use App\Models\Classroom;
use App\Models\Scopes\BerdasarkanUnit;
use App\Models\Student;
use App\Models\StudentPermit;
use App\Models\StudentWelfareSetting;
use App\Models\Unit;
use App\Models\User;
use App\Support\WelfarePeriod;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class StudentWelfareService
{
    public function __construct(protected TeacherAssignmentService $assignments) {}

    public function settings(): StudentWelfareSetting
    {
        return StudentWelfareSetting::query()->firstOrCreate([], StudentWelfareSetting::defaults());
    }

    /**
     * @return array<string, int|float|bool>
     */
    public function settingsPayload(?StudentWelfareSetting $settings = null): array
    {
        $settings ??= $this->settings();

        return [
            'attendance_min_percent' => (float) $settings->attendance_min_percent,
            'permit_max_per_month' => (int) $settings->permit_max_per_month,
            'permit_max_per_semester' => (int) $settings->permit_max_per_semester,
            'violation_warning_points' => (int) $settings->violation_warning_points,
            'violation_max_points' => (int) $settings->violation_max_points,
            'point_ringan' => (int) $settings->point_ringan,
            'point_sedang' => (int) $settings->point_sedang,
            'point_berat' => (int) $settings->point_berat,
            'allow_permit_override' => (bool) $settings->allow_permit_override,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, int|float|bool>
     */
    public function updateSettings(User $user, array $data): array
    {
        $settings = $this->settings();
        $settings->fill($data);
        $settings->updated_by = $user->id;
        $settings->save();

        return $this->settingsPayload($settings->fresh());
    }

    /**
     * @return Collection<int, Classroom>
     */
    public function accessibleClassrooms(User $user): Collection
    {
        $query = Classroom::query()
            ->withoutGlobalScope(BerdasarkanUnit::class)
            ->inEnabledUnits()
            ->where(function ($query): void {
                $query->whereRaw('LOWER(TRIM(classrooms.unit)) = ?', ['lpi'])
                    ->orWhereHas('unit', fn ($query) => $query->whereRaw('LOWER(TRIM(units.kode)) = ?', ['lpi']));
            });

        if ($user->canManageAkhlak()) {
            // Admin Utama dan Kabag Pengajian LPI mengelola seluruh kelas LPI.
        } elseif ($user->isWaliMurid()) {
            $classroomIds = Student::query()
                ->withoutGlobalScope(BerdasarkanUnit::class)
                ->where('parent_id', $user->id)
                ->whereNotNull('classroom_id')
                ->pluck('classroom_id')
                ->unique()
                ->values()
                ->all();

            if ($classroomIds === []) {
                return collect();
            }

            $query->whereIn('classrooms.id', $classroomIds);
        } else {
            return collect();
        }

        return $query
            ->orderBy('grade')
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, Student>
     */
    public function accessibleStudents(User $user, ?int $classroomId = null, bool $activeOnly = true): Collection
    {
        $classroomIds = $this->accessibleClassrooms($user)->pluck('id')->all();

        if ($classroomIds === []) {
            return collect();
        }

        $query = Student::query()
            ->withoutGlobalScope(BerdasarkanUnit::class)
            ->with(['classroom' => fn ($query) => $query->withoutGlobalScope(BerdasarkanUnit::class)])
            ->whereIn('classroom_id', $classroomIds)
            ->when($classroomId !== null, fn ($query) => $query->where('classroom_id', $classroomId));

        if ($user->isWaliMurid()) {
            $query->where('parent_id', $user->id);
        }

        if ($activeOnly) {
            $query->where('status', 'aktif');
        }

        return $query->orderBy('name')->get();
    }

    public function hasLpiAccess(User $user): bool
    {
        return $this->accessibleClassrooms($user)->isNotEmpty();
    }

    public function findStudent(User $user, int $studentId): Student
    {
        $student = Student::query()
            ->withoutGlobalScope(BerdasarkanUnit::class)
            ->with(['classroom' => fn ($query) => $query->withoutGlobalScope(BerdasarkanUnit::class)])
            ->findOrFail($studentId);

        if (! $this->canViewStudent($user, $student)) {
            abort(403);
        }

        return $student;
    }

    public function canViewStudent(User $user, Student $student): bool
    {
        $student->loadMissing(['classroom' => fn ($query) => $query->withoutGlobalScope(BerdasarkanUnit::class)]);

        if (! $this->isLpiClassroom($student->classroom)) {
            return false;
        }

        if ($user->canManageAkhlak()) {
            return true;
        }

        if ($user->isWaliMurid()) {
            return (int) $student->parent_id === (int) $user->id;
        }

        return false;
    }

    public function canManageStudent(User $user, Student $student): bool
    {
        return $user->canManageAkhlak() && $this->canViewStudent($user, $student);
    }

    public function isLpiClassroom(?Classroom $classroom): bool
    {
        if ($classroom === null || ! $this->assignments->isClassroomEnabled($classroom)) {
            return false;
        }

        $code = strtolower(trim((string) $classroom->unit));

        if ($code === '' && $classroom->unit_id !== null) {
            $code = strtolower(trim((string) Unit::query()->whereKey($classroom->unit_id)->value('kode')));
        }

        return $code === 'lpi';
    }

    public function canEditAkhlak(User $user, AkhlakRecord $record): bool
    {
        return $record->student !== null && $this->canManageStudent($user, $record->student);
    }

    public function findAkhlak(int $recordId): AkhlakRecord
    {
        return AkhlakRecord::query()
            ->with([
                'student' => fn ($query) => $query->withoutGlobalScope(BerdasarkanUnit::class),
                'classroom' => fn ($query) => $query->withoutGlobalScope(BerdasarkanUnit::class),
                'recorder',
            ])
            ->findOrFail($recordId);
    }

    public function findPermit(int $permitId): StudentPermit
    {
        return StudentPermit::query()
            ->with([
                'student' => fn ($query) => $query->withoutGlobalScope(BerdasarkanUnit::class),
                'classroom' => fn ($query) => $query->withoutGlobalScope(BerdasarkanUnit::class),
                'requester',
                'decider',
            ])
            ->findOrFail($permitId);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function listAkhlak(User $user, array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $query = AkhlakRecord::query()
            ->with([
                'student' => fn ($query) => $query->withoutGlobalScope(BerdasarkanUnit::class),
                'classroom' => fn ($query) => $query->withoutGlobalScope(BerdasarkanUnit::class),
                'recorder',
            ]);

        if (! empty($filters['student_id'])) {
            $student = $this->findStudent($user, (int) $filters['student_id']);
            $query->where('student_id', $student->id);
        } else {
            $query->whereIn(
                'student_id',
                $this->accessibleStudents($user, isset($filters['classroom_id']) ? (int) $filters['classroom_id'] : null)
                    ->pluck('id')
                    ->all(),
            );
        }

        $query
            ->when(! empty($filters['type']), fn ($query) => $query->where('type', $filters['type']))
            ->when(! empty($filters['status']), fn ($query) => $query->where('status', $filters['status']))
            ->orderByDesc('occurred_at')
            ->orderByDesc('id');

        return $query
            ->paginate($this->clampPerPage($perPage))
            ->through(fn (AkhlakRecord $record): array => $this->payloadAkhlak($record, $user, $user->isWaliMurid()));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createAkhlak(User $user, array $data): AkhlakRecord
    {
        $student = $this->findStudent($user, (int) $data['student_id']);

        if (! $this->canManageStudent($user, $student)) {
            abort(403);
        }

        $settings = $this->settings();
        $type = (string) $data['type'];
        $severity = $type === AkhlakRecord::TYPE_PELANGGARAN ? (string) $data['severity'] : null;
        $occurredAt = (string) $data['occurred_at'];

        return AkhlakRecord::query()->create([
            'student_id' => $student->id,
            'classroom_id' => $student->classroom_id,
            'recorded_by' => $user->id,
            'type' => $type,
            'severity' => $severity,
            'category' => $data['category'] ?? null,
            'description' => $data['description'],
            'points' => $severity !== null ? $settings->pointForSeverity($severity) : 0,
            'occurred_at' => $occurredAt,
            'academic_year' => WelfarePeriod::academicYearFor($student->classroom),
            'semester' => WelfarePeriod::semesterFor($occurredAt),
            'status' => AkhlakRecord::STATUS_ACTIVE,
            'internal_note' => $data['internal_note'] ?? null,
        ])->load([
            'student' => fn ($query) => $query->withoutGlobalScope(BerdasarkanUnit::class),
            'classroom' => fn ($query) => $query->withoutGlobalScope(BerdasarkanUnit::class),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function correctAkhlak(User $user, AkhlakRecord $record, array $data): AkhlakRecord
    {
        if (! $this->canEditAkhlak($user, $record)) {
            abort(403);
        }

        if ($record->status === AkhlakRecord::STATUS_CANCELLED) {
            throw ValidationException::withMessages([
                'record' => 'Catatan yang sudah dibatalkan tidak dapat dikoreksi.',
            ]);
        }

        $settings = $this->settings();
        $type = (string) ($data['type'] ?? $record->type);
        $severity = $type === AkhlakRecord::TYPE_PELANGGARAN
            ? (string) ($data['severity'] ?? $record->severity)
            : null;
        $occurredAt = (string) ($data['occurred_at'] ?? $record->occurred_at->toDateString());

        $record->fill([
            'type' => $type,
            'severity' => $severity,
            'category' => $data['category'] ?? $record->category,
            'description' => $data['description'] ?? $record->description,
            'points' => $severity !== null ? $settings->pointForSeverity($severity) : 0,
            'occurred_at' => $occurredAt,
            'semester' => WelfarePeriod::semesterFor($occurredAt),
            'internal_note' => $data['internal_note'] ?? $record->internal_note,
            'updated_by' => $user->id,
            'correction_reason' => $data['correction_reason'],
            'corrected_at' => now(),
        ]);
        $record->save();

        return $record->load([
            'student' => fn ($query) => $query->withoutGlobalScope(BerdasarkanUnit::class),
            'classroom' => fn ($query) => $query->withoutGlobalScope(BerdasarkanUnit::class),
        ]);
    }

    public function cancelAkhlak(User $user, AkhlakRecord $record, string $reason): AkhlakRecord
    {
        if (! $this->canEditAkhlak($user, $record)) {
            abort(403);
        }

        if ($record->status === AkhlakRecord::STATUS_CANCELLED) {
            throw ValidationException::withMessages([
                'record' => 'Catatan ini sudah dibatalkan.',
            ]);
        }

        $record->update([
            'status' => AkhlakRecord::STATUS_CANCELLED,
            'updated_by' => $user->id,
            'correction_reason' => $reason,
            'corrected_at' => now(),
        ]);

        return $record->load([
            'student' => fn ($query) => $query->withoutGlobalScope(BerdasarkanUnit::class),
            'classroom' => fn ($query) => $query->withoutGlobalScope(BerdasarkanUnit::class),
        ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function listPermits(User $user, array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $query = StudentPermit::query()
            ->with([
                'student' => fn ($query) => $query->withoutGlobalScope(BerdasarkanUnit::class),
                'classroom' => fn ($query) => $query->withoutGlobalScope(BerdasarkanUnit::class),
                'requester',
                'decider',
            ]);

        if (! empty($filters['student_id'])) {
            $student = $this->findStudent($user, (int) $filters['student_id']);
            $query->where('student_id', $student->id);
        } else {
            $query->whereIn(
                'student_id',
                $this->accessibleStudents($user, isset($filters['classroom_id']) ? (int) $filters['classroom_id'] : null)
                    ->pluck('id')
                    ->all(),
            );
        }

        $query
            ->when(! empty($filters['status']), fn ($query) => $query->where('status', $filters['status']))
            ->when(! empty($filters['type']), fn ($query) => $query->where('type', $filters['type']))
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->orderByDesc('start_date')
            ->orderByDesc('id');

        return $query
            ->paginate($this->clampPerPage($perPage))
            ->through(fn (StudentPermit $permit): array => $this->payloadPermit($permit, $user, $user->isWaliMurid()));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createPermit(User $user, array $data): StudentPermit
    {
        $student = $this->findStudent($user, (int) $data['student_id']);

        if (! $this->canManageStudent($user, $student)) {
            abort(403);
        }

        $startDate = (string) $data['start_date'];

        return StudentPermit::query()->create([
            'student_id' => $student->id,
            'classroom_id' => $student->classroom_id,
            'requested_by' => $user->id,
            'type' => (string) $data['type'],
            'reason' => $data['reason'],
            'start_date' => $startDate,
            'end_date' => (string) $data['end_date'],
            'status' => StudentPermit::STATUS_PENDING,
            'academic_year' => WelfarePeriod::academicYearFor($student->classroom),
            'semester' => WelfarePeriod::semesterFor($startDate),
        ])->load([
            'student' => fn ($query) => $query->withoutGlobalScope(BerdasarkanUnit::class),
            'classroom' => fn ($query) => $query->withoutGlobalScope(BerdasarkanUnit::class),
        ]);
    }

    public function approvePermit(
        User $user,
        StudentPermit $permit,
        ?string $overrideReason = null,
        ?string $decisionNote = null,
    ): StudentPermit {
        if (! $user->canManageAkhlak()) {
            abort(403);
        }

        if (! $permit->isPending()) {
            throw ValidationException::withMessages([
                'permit' => 'Hanya izin berstatus menunggu yang dapat disetujui.',
            ]);
        }

        $settings = $this->settings();

        if (! $settings->allow_permit_override) {
            throw ValidationException::withMessages([
                'override_reason' => 'Persetujuan melebihi batas izin sedang dinonaktifkan.',
            ]);
        }

        $permit->loadMissing('student.classroom');
        $counts = $this->permitCounts($permit->student, $permit->start_date);
        $exceedsMonthly = $counts['month'] >= (int) $settings->permit_max_per_month;
        $exceedsSemester = $counts['semester'] >= (int) $settings->permit_max_per_semester;
        $isOverride = $exceedsMonthly || $exceedsSemester;
        $overrideReason = trim((string) $overrideReason);

        if ($isOverride && $overrideReason === '') {
            throw ValidationException::withMessages([
                'override_reason' => 'Alasan override wajib diisi karena izin melebihi batas.',
            ]);
        }

        $permit->update([
            'status' => StudentPermit::STATUS_APPROVED,
            'decided_by' => $user->id,
            'decided_at' => now(),
            'decision_note' => $decisionNote,
            'is_override' => $isOverride,
            'override_reason' => $isOverride ? $overrideReason : null,
        ]);

        return $permit->load([
            'student' => fn ($query) => $query->withoutGlobalScope(BerdasarkanUnit::class),
            'classroom' => fn ($query) => $query->withoutGlobalScope(BerdasarkanUnit::class),
        ]);
    }

    public function rejectPermit(User $user, StudentPermit $permit, ?string $decisionNote = null): StudentPermit
    {
        if (! $user->canManageAkhlak()) {
            abort(403);
        }

        if (! $permit->isPending()) {
            throw ValidationException::withMessages([
                'permit' => 'Hanya izin berstatus menunggu yang dapat ditolak.',
            ]);
        }

        $permit->update([
            'status' => StudentPermit::STATUS_REJECTED,
            'decided_by' => $user->id,
            'decided_at' => now(),
            'decision_note' => $decisionNote,
            'is_override' => false,
            'override_reason' => null,
        ]);

        return $permit->load([
            'student' => fn ($query) => $query->withoutGlobalScope(BerdasarkanUnit::class),
            'classroom' => fn ($query) => $query->withoutGlobalScope(BerdasarkanUnit::class),
        ]);
    }

    public function cancelPermit(User $user, StudentPermit $permit, ?string $decisionNote = null): StudentPermit
    {
        if (! $user->canManageAkhlak()) {
            abort(403);
        }

        if (in_array($permit->status, [StudentPermit::STATUS_CANCELLED, StudentPermit::STATUS_REJECTED], true)) {
            throw ValidationException::withMessages([
                'permit' => 'Izin ini tidak dapat dibatalkan lagi.',
            ]);
        }

        $permit->update([
            'status' => StudentPermit::STATUS_CANCELLED,
            'decided_by' => $user->id,
            'decided_at' => now(),
            'decision_note' => $decisionNote,
        ]);

        return $permit->load([
            'student' => fn ($query) => $query->withoutGlobalScope(BerdasarkanUnit::class),
            'classroom' => fn ($query) => $query->withoutGlobalScope(BerdasarkanUnit::class),
        ]);
    }

    /**
     * @return array{month: int, semester: int, academic_year: string, semester_name: string, month_start: string, month_end: string, semester_start: string, semester_end: string}
     */
    public function permitCounts(Student $student, DateTimeInterface|string $date): array
    {
        $student->loadMissing('classroom');

        $academicYear = WelfarePeriod::academicYearFor($student->classroom);
        $semester = WelfarePeriod::semesterFor($date);
        [$monthStart, $monthEnd] = WelfarePeriod::monthRange($date);
        [$semesterStart, $semesterEnd] = WelfarePeriod::semesterRange($academicYear, $semester);

        $base = StudentPermit::query()
            ->where('student_id', $student->id)
            ->where('status', StudentPermit::STATUS_APPROVED);

        $month = (clone $base)
            ->where('start_date', '<=', $monthEnd)
            ->where('end_date', '>=', $monthStart)
            ->count();

        $semesterCount = (clone $base)
            ->where('start_date', '<=', $semesterEnd)
            ->where('end_date', '>=', $semesterStart)
            ->count();

        return [
            'month' => $month,
            'semester' => $semesterCount,
            'academic_year' => $academicYear,
            'semester_name' => $semester,
            'month_start' => $monthStart,
            'month_end' => $monthEnd,
            'semester_start' => $semesterStart,
            'semester_end' => $semesterEnd,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function meta(User $user): array
    {
        $classrooms = $this->accessibleClassrooms($user);
        $students = $this->accessibleStudents($user);

        return [
            'has_lpi' => $classrooms->isNotEmpty(),
            'can_manage_akhlak' => $user->canManageAkhlak(),
            'classrooms' => $classrooms->map(fn (Classroom $classroom): array => $this->payloadClassroom($classroom))->values()->all(),
            'students' => $students->map(fn (Student $student): array => $this->payloadStudent($student))->values()->all(),
            'settings' => $user->canManageAkhlak() ? $this->settingsPayload() : null,
            'permissions' => [
                'manage' => $user->canManageAkhlak(),
                'approve' => $user->canManageAkhlak(),
                'settings' => $user->canManageAkhlak(),
            ],
            'pending_permits' => $user->canManageAkhlak()
                ? StudentPermit::query()
                    ->where('status', StudentPermit::STATUS_PENDING)
                    ->whereIn('student_id', $students->pluck('id')->all())
                    ->count()
                : 0,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function payloadAkhlak(AkhlakRecord $record, User $user, bool $forWali = false): array
    {
        $payload = [
            'id' => $record->id,
            'student_id' => $record->student_id,
            'student_name' => (string) $record->student?->name,
            'classroom_id' => $record->classroom_id,
            'classroom_name' => (string) $record->classroom?->name,
            'classroom_label' => $record->classroom?->unitLabel(),
            'type' => $record->type,
            'type_label' => $this->akhlakTypeLabel($record->type),
            'severity' => $record->severity,
            'severity_label' => $record->severity !== null ? $this->severityLabel($record->severity) : null,
            'category' => $record->category,
            'description' => $record->description,
            'points' => (int) $record->points,
            'occurred_at' => $record->occurred_at?->toDateString(),
            'academic_year' => $record->academic_year,
            'semester' => $record->semester,
            'status' => $record->status,
            'status_label' => $this->akhlakStatusLabel($record->status),
            'can_edit' => ! $forWali && $record->status === AkhlakRecord::STATUS_ACTIVE && $this->canEditAkhlak($user, $record),
            'can_cancel' => ! $forWali && $record->status === AkhlakRecord::STATUS_ACTIVE && $this->canEditAkhlak($user, $record),
        ];

        if (! $forWali) {
            $payload['internal_note'] = $record->internal_note;
            $payload['correction_reason'] = $record->correction_reason;
            $payload['corrected_at'] = $record->corrected_at?->toDateTimeString();
            $payload['recorded_by'] = $record->recorded_by;
            $payload['recorded_by_name'] = (string) $record->recorder?->name;
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    public function payloadPermit(StudentPermit $permit, User $user, bool $forWali = false): array
    {
        $payload = [
            'id' => $permit->id,
            'student_id' => $permit->student_id,
            'student_name' => (string) $permit->student?->name,
            'classroom_id' => $permit->classroom_id,
            'classroom_name' => (string) $permit->classroom?->name,
            'classroom_label' => $permit->classroom?->unitLabel(),
            'type' => $permit->type,
            'type_label' => $this->permitTypeLabel($permit->type),
            'reason' => $permit->reason,
            'start_date' => $permit->start_date?->toDateString(),
            'end_date' => $permit->end_date?->toDateString(),
            'status' => $permit->status,
            'status_label' => $this->permitStatusLabel($permit->status),
            'is_override' => (bool) $permit->is_override,
            'can_approve' => ! $forWali && $user->canManageAkhlak() && $permit->isPending(),
            'can_reject' => ! $forWali && $user->canManageAkhlak() && $permit->isPending(),
            'can_cancel' => ! $forWali && $user->canManageAkhlak()
                && $permit->status !== StudentPermit::STATUS_CANCELLED
                && $permit->status !== StudentPermit::STATUS_REJECTED,
        ];

        if (! $forWali) {
            $payload['requested_by'] = $permit->requested_by;
            $payload['requested_by_name'] = (string) $permit->requester?->name;
            $payload['decided_by'] = $permit->decided_by;
            $payload['decided_by_name'] = (string) $permit->decider?->name;
            $payload['decided_at'] = $permit->decided_at?->toDateTimeString();
            $payload['decision_note'] = $permit->decision_note;
            $payload['override_reason'] = $permit->override_reason;
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    public function payloadStudent(Student $student): array
    {
        $student->loadMissing(['classroom' => fn ($query) => $query->withoutGlobalScope(BerdasarkanUnit::class)]);

        return [
            'id' => $student->id,
            'nis' => $student->nis,
            'name' => $student->name,
            'classroom_id' => $student->classroom_id,
            'classroom_name' => (string) $student->classroom?->name,
            'classroom_label' => $student->classroom?->unitLabel(),
            'jenjang' => (string) $student->classroom?->jenjang,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function payloadClassroom(Classroom $classroom): array
    {
        return [
            'id' => $classroom->id,
            'name' => $classroom->name,
            'unit' => strtoupper((string) $classroom->unit),
            'unit_label' => $classroom->unitLabel(),
            'jenjang' => (string) $classroom->jenjang,
            'grade' => (int) $classroom->grade,
            'academic_year' => $classroom->academic_year,
        ];
    }

    public function akhlakTypeLabel(string $type): string
    {
        return match ($type) {
            AkhlakRecord::TYPE_APRESIASI => 'Apresiasi',
            default => 'Pelanggaran',
        };
    }

    public function severityLabel(string $severity): string
    {
        return match ($severity) {
            AkhlakRecord::SEVERITY_RINGAN => 'Ringan',
            AkhlakRecord::SEVERITY_SEDANG => 'Sedang',
            AkhlakRecord::SEVERITY_BERAT => 'Berat',
            default => ucfirst($severity),
        };
    }

    public function akhlakStatusLabel(string $status): string
    {
        return match ($status) {
            AkhlakRecord::STATUS_CANCELLED => 'Dibatalkan',
            default => 'Aktif',
        };
    }

    public function permitTypeLabel(string $type): string
    {
        return match ($type) {
            StudentPermit::TYPE_SAKIT => 'Sakit',
            default => 'Izin',
        };
    }

    public function permitStatusLabel(string $status): string
    {
        return match ($status) {
            StudentPermit::STATUS_APPROVED => 'Disetujui',
            StudentPermit::STATUS_REJECTED => 'Ditolak',
            StudentPermit::STATUS_CANCELLED => 'Dibatalkan',
            default => 'Menunggu',
        };
    }

    public function currentSemester(): string
    {
        return WelfarePeriod::semesterFor(CarbonImmutable::now());
    }

    private function clampPerPage(int $perPage): int
    {
        return max(5, min(100, $perPage));
    }
}
