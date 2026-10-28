<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AkhlakRecord;
use App\Models\Scopes\BerdasarkanUnit;
use App\Models\StudentPermit;
use App\Models\User;
use App\Services\EligibilityService;
use App\Services\StudentWelfareService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WaliWelfareController extends Controller
{
    public function __construct(
        protected StudentWelfareService $welfare,
        protected EligibilityService $eligibility,
    ) {}

    public function overview(Request $request): JsonResponse
    {
        $this->authorize('viewAnyForWali', AkhlakRecord::class);

        /** @var User $user */
        $user = $request->user();
        $students = $this->welfare->accessibleStudents($user);

        if ($students->isEmpty()) {
            return response()->json([
                'students' => [],
                'selected_student_id' => null,
                'student' => null,
                'eligibility' => null,
                'akhlak' => [],
                'permits' => [],
            ]);
        }

        $selectedId = (int) ($request->query('student_id') ?? $students->first()->id);
        $student = $this->welfare->findStudent($user, $selectedId);

        $akhlak = AkhlakRecord::query()
            ->with([
                'student',
                'classroom' => fn ($query) => $query->withoutGlobalScope(BerdasarkanUnit::class),
            ])
            ->where('student_id', $student->id)
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->limit(100)
            ->get()
            ->map(fn (AkhlakRecord $record): array => $this->welfare->payloadAkhlak($record, $user, true))
            ->values()
            ->all();

        $permits = StudentPermit::query()
            ->with([
                'student',
                'classroom' => fn ($query) => $query->withoutGlobalScope(BerdasarkanUnit::class),
            ])
            ->where('student_id', $student->id)
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->limit(100)
            ->get()
            ->map(fn (StudentPermit $permit): array => $this->welfare->payloadPermit($permit, $user, true))
            ->values()
            ->all();

        return response()->json([
            'students' => $students->map(fn ($item): array => $this->welfare->payloadStudent($item))->values()->all(),
            'selected_student_id' => $student->id,
            'student' => $this->welfare->payloadStudent($student),
            'eligibility' => $this->eligibility->evaluate($student),
            'akhlak' => $akhlak,
            'permits' => $permits,
        ]);
    }
}
