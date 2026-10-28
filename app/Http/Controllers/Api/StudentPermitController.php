<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StudentPermit;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\EligibilityService;
use App\Services\StudentWelfareService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StudentPermitController extends Controller
{
    public function __construct(
        protected StudentWelfareService $welfare,
        protected EligibilityService $eligibility,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', StudentPermit::class);

        $filters = $request->validate([
            'student_id' => ['nullable', 'integer'],
            'classroom_id' => ['nullable', 'integer'],
            'type' => ['nullable', Rule::in([StudentPermit::TYPE_IZIN, StudentPermit::TYPE_SAKIT])],
            'status' => ['nullable', Rule::in([
                StudentPermit::STATUS_PENDING,
                StudentPermit::STATUS_APPROVED,
                StudentPermit::STATUS_REJECTED,
                StudentPermit::STATUS_CANCELLED,
            ])],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
        ]);

        /** @var User $user */
        $user = $request->user();

        return response()->json($this->welfare->listPermits($user, $filters, (int) ($filters['per_page'] ?? 20)));
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', StudentPermit::class);

        $data = $request->validate([
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'type' => ['required', Rule::in([StudentPermit::TYPE_IZIN, StudentPermit::TYPE_SAKIT])],
            'reason' => ['required', 'string', 'max:2000'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        /** @var User $user */
        $user = $request->user();
        $permit = $this->welfare->createPermit($user, $data);

        ActivityLogger::log('permit.create', $user, StudentPermit::class, $permit->id, [
            'student_id' => $permit->student_id,
            'type' => $permit->type,
            'start_date' => $permit->start_date?->toDateString(),
            'end_date' => $permit->end_date?->toDateString(),
        ]);

        return response()->json(
            $this->welfare->payloadPermit($this->welfare->findPermit($permit->id), $user),
            201,
        );
    }

    public function approve(Request $request, int $id): JsonResponse
    {
        $permit = $this->welfare->findPermit($id);
        $this->authorize('approve', $permit);

        $data = $request->validate([
            'override_reason' => ['nullable', 'string', 'max:1000'],
            'decision_note' => ['nullable', 'string', 'max:1000'],
        ]);

        /** @var User $user */
        $user = $request->user();
        $approved = $this->welfare->approvePermit(
            $user,
            $permit,
            $data['override_reason'] ?? null,
            $data['decision_note'] ?? null,
        );
        $this->eligibility->evaluate($approved->student);

        ActivityLogger::log('permit.approve', $user, StudentPermit::class, $approved->id, [
            'student_id' => $approved->student_id,
            'is_override' => $approved->is_override,
            'override_reason' => $approved->override_reason,
        ]);

        return response()->json($this->welfare->payloadPermit($this->welfare->findPermit($approved->id), $user));
    }

    public function reject(Request $request, int $id): JsonResponse
    {
        $permit = $this->welfare->findPermit($id);
        $this->authorize('reject', $permit);

        $data = $request->validate([
            'decision_note' => ['nullable', 'string', 'max:1000'],
        ]);

        /** @var User $user */
        $user = $request->user();
        $rejected = $this->welfare->rejectPermit($user, $permit, $data['decision_note'] ?? null);
        $this->eligibility->evaluate($rejected->student);

        ActivityLogger::log('permit.reject', $user, StudentPermit::class, $rejected->id, [
            'student_id' => $rejected->student_id,
            'decision_note' => $rejected->decision_note,
        ]);

        return response()->json($this->welfare->payloadPermit($this->welfare->findPermit($rejected->id), $user));
    }

    public function cancel(Request $request, int $id): JsonResponse
    {
        $permit = $this->welfare->findPermit($id);
        $this->authorize('cancel', $permit);

        $data = $request->validate([
            'decision_note' => ['nullable', 'string', 'max:1000'],
        ]);

        /** @var User $user */
        $user = $request->user();
        $cancelled = $this->welfare->cancelPermit($user, $permit, $data['decision_note'] ?? null);
        $this->eligibility->evaluate($cancelled->student);

        ActivityLogger::log('permit.cancel', $user, StudentPermit::class, $cancelled->id, [
            'student_id' => $cancelled->student_id,
            'decision_note' => $cancelled->decision_note,
        ]);

        return response()->json($this->welfare->payloadPermit($this->welfare->findPermit($cancelled->id), $user));
    }
}
