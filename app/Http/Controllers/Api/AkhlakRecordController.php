<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AkhlakRecord;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\EligibilityService;
use App\Services\StudentWelfareService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AkhlakRecordController extends Controller
{
    public function __construct(
        protected StudentWelfareService $welfare,
        protected EligibilityService $eligibility,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', AkhlakRecord::class);

        $filters = $request->validate([
            'student_id' => ['nullable', 'integer'],
            'classroom_id' => ['nullable', 'integer'],
            'type' => ['nullable', Rule::in([AkhlakRecord::TYPE_PELANGGARAN, AkhlakRecord::TYPE_APRESIASI])],
            'status' => ['nullable', Rule::in([AkhlakRecord::STATUS_ACTIVE, AkhlakRecord::STATUS_CANCELLED])],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
        ]);

        /** @var User $user */
        $user = $request->user();

        return response()->json($this->welfare->listAkhlak($user, $filters, (int) ($filters['per_page'] ?? 20)));
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', AkhlakRecord::class);

        $data = $request->validate($this->rules());

        /** @var User $user */
        $user = $request->user();
        $record = $this->welfare->createAkhlak($user, $data);
        $this->eligibility->evaluate($record->student);

        ActivityLogger::log('akhlak.create', $user, AkhlakRecord::class, $record->id, [
            'student_id' => $record->student_id,
            'type' => $record->type,
            'severity' => $record->severity,
            'points' => $record->points,
        ]);

        return response()->json($this->welfare->payloadAkhlak($this->welfare->findAkhlak($record->id), $user), 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $record = $this->welfare->findAkhlak($id);
        $this->authorize('update', $record);

        $data = $request->validate([
            'type' => ['sometimes', Rule::in([AkhlakRecord::TYPE_PELANGGARAN, AkhlakRecord::TYPE_APRESIASI])],
            'severity' => ['nullable', 'required_if:type,pelanggaran', Rule::in([
                AkhlakRecord::SEVERITY_RINGAN,
                AkhlakRecord::SEVERITY_SEDANG,
                AkhlakRecord::SEVERITY_BERAT,
            ])],
            'category' => ['nullable', 'string', 'max:100'],
            'description' => ['sometimes', 'string', 'max:2000'],
            'occurred_at' => ['sometimes', 'date', 'before_or_equal:today'],
            'internal_note' => ['nullable', 'string', 'max:2000'],
            'correction_reason' => ['required', 'string', 'max:1000'],
        ]);

        /** @var User $user */
        $user = $request->user();
        $updated = $this->welfare->correctAkhlak($user, $record, $data);
        $this->eligibility->evaluate($updated->student);

        ActivityLogger::log('akhlak.update', $user, AkhlakRecord::class, $updated->id, [
            'student_id' => $updated->student_id,
            'changes' => array_keys($data),
        ]);

        return response()->json($this->welfare->payloadAkhlak($this->welfare->findAkhlak($updated->id), $user));
    }

    public function cancel(Request $request, int $id): JsonResponse
    {
        $record = $this->welfare->findAkhlak($id);
        $this->authorize('cancel', $record);

        $data = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        /** @var User $user */
        $user = $request->user();
        $cancelled = $this->welfare->cancelAkhlak($user, $record, $data['reason']);
        $this->eligibility->evaluate($cancelled->student);

        ActivityLogger::log('akhlak.cancel', $user, AkhlakRecord::class, $cancelled->id, [
            'student_id' => $cancelled->student_id,
            'reason' => $data['reason'],
        ]);

        return response()->json($this->welfare->payloadAkhlak($this->welfare->findAkhlak($cancelled->id), $user));
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function rules(): array
    {
        return [
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'type' => ['required', Rule::in([AkhlakRecord::TYPE_PELANGGARAN, AkhlakRecord::TYPE_APRESIASI])],
            'severity' => ['nullable', 'required_if:type,pelanggaran', Rule::in([
                AkhlakRecord::SEVERITY_RINGAN,
                AkhlakRecord::SEVERITY_SEDANG,
                AkhlakRecord::SEVERITY_BERAT,
            ])],
            'category' => ['nullable', 'string', 'max:100'],
            'description' => ['required', 'string', 'max:2000'],
            'occurred_at' => ['required', 'date', 'before_or_equal:today'],
            'internal_note' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
