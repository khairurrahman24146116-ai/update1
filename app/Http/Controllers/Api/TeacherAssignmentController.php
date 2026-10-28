<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TeacherAssignmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TeacherAssignmentController extends Controller
{
    public function __construct(protected TeacherAssignmentService $assignments) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'assignments' => $this->assignments->assignmentsFor($request->user()),
        ]);
    }
}
