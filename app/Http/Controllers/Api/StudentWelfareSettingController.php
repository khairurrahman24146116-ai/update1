<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StudentWelfareSetting;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\StudentWelfareService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentWelfareSettingController extends Controller
{
    public function __construct(protected StudentWelfareService $welfare) {}

    public function show(Request $request): JsonResponse
    {
        $this->authorize('view', StudentWelfareSetting::class);

        return response()->json($this->welfare->settingsPayload());
    }

    public function update(Request $request): JsonResponse
    {
        $this->authorize('update', StudentWelfareSetting::class);

        $data = $request->validate([
            'attendance_min_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'permit_max_per_month' => ['required', 'integer', 'min:0', 'max:31'],
            'permit_max_per_semester' => ['required', 'integer', 'min:0', 'max:100'],
            'violation_warning_points' => ['required', 'integer', 'min:0', 'max:1000', 'lte:violation_max_points'],
            'violation_max_points' => ['required', 'integer', 'min:0', 'max:1000'],
            'point_ringan' => ['required', 'integer', 'min:0', 'max:100', 'lte:point_sedang'],
            'point_sedang' => ['required', 'integer', 'min:0', 'max:100', 'lte:point_berat'],
            'point_berat' => ['required', 'integer', 'min:0', 'max:100'],
            'allow_permit_override' => ['required', 'boolean'],
        ]);

        /** @var User $user */
        $user = $request->user();
        $settings = $this->welfare->updateSettings($user, $data);

        ActivityLogger::log('welfare.settings.update', $user, StudentWelfareSetting::class, 1, [
            'settings' => $settings,
        ]);

        return response()->json($settings);
    }
}
