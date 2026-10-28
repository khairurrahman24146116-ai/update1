<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\StudentWelfareService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WelfareMetaController extends Controller
{
    public function __construct(protected StudentWelfareService $welfare) {}

    public function __invoke(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        abort_unless(
            $user->canManageAkhlak() || $user->isWaliMurid(),
            403,
            'Fitur Akhlak & Izin hanya untuk Admin Utama atau Kabag Pengajian LPI.',
        );

        return response()->json($this->welfare->meta($user));
    }
}
