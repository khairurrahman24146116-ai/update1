<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AkhlakRecord;
use App\Models\User;
use App\Services\EligibilityService;
use App\Services\StudentWelfareService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentEligibilityController extends Controller
{
    public function __construct(
        protected StudentWelfareService $welfare,
        protected EligibilityService $eligibility,
    ) {}

    public function show(Request $request, int $studentId): JsonResponse
    {
        $this->authorize('viewAny', AkhlakRecord::class);

        /** @var User $user */
        $user = $request->user();
        $student = $this->welfare->findStudent($user, $studentId);

        return response()->json($this->eligibility->evaluate($student));
    }
}
