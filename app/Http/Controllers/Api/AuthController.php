<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Unit;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $v = $request->validate(['identity' => 'required|string', 'email' => 'sometimes|email', 'password' => 'required']);
        $identity = trim($v['identity'] ?? $v['email'] ?? '');
        if (filter_var($identity, FILTER_VALIDATE_EMAIL) === false) {
            $student = Student::where('nisn', $identity)->first();
            if (! $student || ! $student->parent_id) {
                $this->logFailure(null, 'account_not_found', $identity, 'nisn');

                return response()->json([
                    'message' => 'Akun belum terdaftar. Periksa kembali NISN Anda.',
                    'code' => 'account_not_found',
                ], 401);
            }
            $wali = User::where('id', $student->parent_id)->where('role', 'wali_murid')->where('is_active', true)->first();
            if (! $wali) {
                $this->logFailure(null, 'account_not_found', $identity, 'nisn');

                return response()->json([
                    'message' => 'Akun belum terdaftar. Periksa kembali NISN Anda.',
                    'code' => 'account_not_found',
                ], 401);
            }
            if (! Hash::check($v['password'], $wali->password)) {
                $this->logFailure($wali, 'invalid_credentials', $identity, 'nisn');

                return response()->json([
                    'message' => 'Username dan password salah.',
                    'code' => 'invalid_credentials',
                ], 401);
            }
            if ($response = $this->fullAccessResponse($wali, 'nisn')) {
                return $response;
            }
            auth()->setUser($wali);
            ActivityLogger::log('login', $wali, null, null, ['channel' => 'nisn']);

            return ['token' => $wali->createToken('api')->plainTextToken, 'user' => $this->userPayload($wali)];
        }

        $user = User::where('email', $identity)->first();
        if (! $user) {
            $this->logFailure(null, 'account_not_found', $identity, 'email');

            return response()->json([
                'message' => 'Akun belum terdaftar. Periksa kembali email Anda.',
                'code' => 'account_not_found',
            ], 401);
        }
        if (! Hash::check($v['password'], $user->password)) {
            $this->logFailure($user, 'invalid_credentials', $identity, 'email');

            return response()->json([
                'message' => 'Username dan password salah.',
                'code' => 'invalid_credentials',
            ], 401);
        }
        if (! $user->is_active) {
            $this->logFailure($user, 'account_inactive', $identity, 'email');

            return response()->json([
                'message' => 'Akun Anda nonaktif. Hubungi administrator.',
                'code' => 'account_inactive',
            ], 403);
        }
        if ($response = $this->fullAccessResponse($user, 'email')) {
            return $response;
        }

        auth()->setUser($user);
        ActivityLogger::log('login', $user, null, null, ['channel' => 'email']);

        return ['token' => $user->createToken('api')->plainTextToken, 'user' => $this->userPayload($user)];
    }

    public function logout(Request $request)
    {
        $u = $request->user();
        $token = $u->currentAccessToken();

        if ($token && method_exists($token, 'delete')) {
            $token->delete();
        }

        ActivityLogger::log('logout', $u, null, null, ['via' => 'api/token']);

        return response()->noContent();
    }

    public function me(Request $request)
    {
        return $this->userPayload($request->user());
    }

    /**
     * Payload akun + izin efektif agar frontend dapat menyembunyikan menu
     * yang tidak diizinkan. Penegakan tetap dilakukan di backend.
     */
    private function userPayload(User $user): array
    {
        return array_merge($user->toArray(), $user->permissionPayload(), [
            'can_access_finance' => $user->canAccessFinance(),
            'finance_unit_codes' => $user->financeUnitCodes(),
            'enabled_units' => Unit::enabledCodes(),
        ]);
    }

    private function fullAccessResponse(User $user, string $channel): ?JsonResponse
    {
        if (! $user->hasFullPermissionAccess()) {
            return null;
        }

        ActivityLogger::log('login.blocked', $user, null, null, [
            'reason' => 'all_permissions',
            'channel' => $channel,
        ]);

        return response()->json([
            'message' => 'Akun ini memiliki seluruh izin. Akun non-admin tidak diizinkan memakai akses penuh. Hubungi admin; lebih baik gunakan akun admin.',
            'code' => 'all_permissions',
        ], 403);
    }

    private function logFailure(?User $user, string $reason, string $identity, string $channel): void
    {
        $metadata = ['reason' => $reason, 'channel' => $channel];

        if ($user) {
            $metadata['identity'] = $user->email;
            ActivityLogger::log('login.failed', $user, null, null, $metadata);

            return;
        }

        $metadata['identity'] = $identity;
        ActivityLogger::logGuest('login.failed', $metadata);
    }
}
