<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\User;
use App\Services\ActivityLogger;
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
                return response()->json([
                    'message' => 'Akun belum terdaftar. Periksa kembali NISN Anda.',
                    'code' => 'account_not_found',
                ], 401);
            }
            $wali = User::where('id', $student->parent_id)->where('role', 'wali_murid')->where('is_active', true)->first();
            if (! $wali) {
                return response()->json([
                    'message' => 'Akun belum terdaftar. Periksa kembali NISN Anda.',
                    'code' => 'account_not_found',
                ], 401);
            }
            if (! Hash::check($v['password'], $wali->password)) {
                return response()->json([
                    'message' => 'Username dan password salah.',
                    'code' => 'invalid_credentials',
                ], 401);
            }
            auth()->setUser($wali);

            return ['token' => $wali->createToken('api')->plainTextToken, 'user' => $this->userPayload($wali)];
        }

        $user = User::where('email', $identity)->first();
        if (! $user) {
            return response()->json([
                'message' => 'Akun belum terdaftar. Periksa kembali email Anda.',
                'code' => 'account_not_found',
            ], 401);
        }
        if (! Hash::check($v['password'], $user->password)) {
            return response()->json([
                'message' => 'Username dan password salah.',
                'code' => 'invalid_credentials',
            ], 401);
        }
        if (! $user->is_active) {
            return response()->json([
                'message' => 'Akun Anda nonaktif. Hubungi administrator.',
                'code' => 'account_inactive',
            ], 403);
        }

        auth()->setUser($user);

        return ['token' => $user->createToken('api')->plainTextToken, 'user' => $this->userPayload($user)];
    }

    public function logout(Request $request)
    {
        $u = $request->user();
        $u->currentAccessToken()->delete();
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
        return array_merge($user->toArray(), $user->permissionPayload());
    }
}
