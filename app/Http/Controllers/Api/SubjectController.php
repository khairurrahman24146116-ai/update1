<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\Subject;
use App\Models\SubjectScope;
use App\Models\Unit;
use App\Services\ActivityLogger;
use App\Services\SubjectService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SubjectController extends Controller
{
    public function __construct(protected SubjectService $service) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Subject::class);

        $perPage = min(max((int) $request->query('per_page', 15), 1), 100);
        $subjects = $this->service->list([
            'search' => $request->query('search'),
            'jenis' => $request->query('jenis'),
            'unit' => $request->query('unit'),
            'jenjang' => $request->query('jenjang'),
            'status' => $request->query('status'),
            'active_only' => $request->boolean('active_only'),
        ], $perPage);

        return $subjects->through(fn (Subject $subject): array => $this->payload($subject));
    }

    public function show(int $id)
    {
        $subject = $this->service->find($id);
        $this->authorize('view', $subject);

        return $this->payload($subject);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Subject::class);
        $data = $request->validate($this->rules());
        $this->validatePlacements($data['placements']);

        $subject = $this->service->create($data, $data['placements']);
        ActivityLogger::log('subject.create', $request->user(), Subject::class, $subject->id, [
            'name' => $subject->name,
            'jenis' => $subject->jenis,
            'is_active' => $subject->is_active,
            'placements' => $data['placements'],
        ]);

        return response()->json($this->payload($subject), 201);
    }

    public function update(Request $request, int $id)
    {
        $subject = $this->service->find($id);
        $this->authorize('update', $subject);
        $data = $request->validate($this->rules($subject));

        if (array_key_exists('placements', $data)) {
            $this->validatePlacements($data['placements']);
        }

        $updated = $this->service->update($subject, $data, $data['placements'] ?? null);
        ActivityLogger::log('subject.update', $request->user(), Subject::class, $id, [
            'changes' => array_keys($data),
            'placements' => $data['placements'] ?? null,
        ]);

        return $this->payload($updated);
    }

    public function destroy(int $id, Request $request)
    {
        $subject = $this->service->find($id);
        $this->authorize('delete', $subject);
        $this->service->delete($subject);
        ActivityLogger::log('subject.delete', $request->user(), Subject::class, $id);

        return response()->noContent();
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function rules(?Subject $subject = null): array
    {
        $creating = $subject === null;

        return [
            'name' => [$creating ? 'required' : 'sometimes', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:20', Rule::unique('subjects', 'code')->ignore($subject?->id)],
            'jenis' => [$creating ? 'required' : 'sometimes', Rule::in([Subject::JENIS_FORMAL, Subject::JENIS_NON_FORMAL])],
            'is_active' => ['sometimes', 'boolean'],
            'placements' => [$creating ? 'required' : 'sometimes', 'array', 'min:1'],
            'placements.*.unit' => ['required', 'string', Rule::in(Unit::enabledCodes())],
            'placements.*.jenjang' => ['required', 'string', Rule::in([
                Classroom::JENJANG_TSANAWIYAH,
                Classroom::JENJANG_ALIYAH,
                Classroom::JENJANG_SMA,
                Classroom::JENJANG_SMP,
            ])],
        ];
    }

    /**
     * @param  list<array{unit: string, jenjang: string}>  $placements
     */
    private function validatePlacements(array $placements): void
    {
        $allowed = [
            SubjectScope::UNIT_LPI => [Classroom::JENJANG_TSANAWIYAH, Classroom::JENJANG_ALIYAH],
            SubjectScope::UNIT_SMA => [Classroom::JENJANG_SMA],
            SubjectScope::UNIT_SMP => [Classroom::JENJANG_SMP],
        ];

        foreach ($placements as $placement) {
            $unit = strtolower((string) $placement['unit']);
            $jenjang = (string) $placement['jenjang'];

            if (! in_array($jenjang, $allowed[$unit] ?? [], true)) {
                throw ValidationException::withMessages([
                    'placements' => "Jenjang {$jenjang} tidak sesuai dengan unit {$unit}.",
                ]);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Subject $subject): array
    {
        $usage = [
            'assignments' => (int) ($subject->teacher_subjects_count ?? 0),
            'schedules' => (int) ($subject->schedules_count ?? 0),
            'scores' => (int) ($subject->scores_count ?? 0),
        ];

        return [
            'id' => $subject->id,
            'name' => $subject->name,
            'code' => $subject->code,
            'jenis' => $subject->jenis,
            'jenis_label' => $subject->jenis === Subject::JENIS_NON_FORMAL ? 'Non Formal' : 'Formal',
            'is_active' => (bool) $subject->is_active,
            'scopes' => $subject->scopes->map(fn (SubjectScope $scope): array => [
                'unit' => strtolower($scope->unit),
                'jenjang' => $scope->jenjang,
                'label' => $scope->label(),
            ])->values()->all(),
            'usage' => [...$usage, 'total' => array_sum($usage)],
            'can_delete' => array_sum($usage) === 0,
        ];
    }
}
