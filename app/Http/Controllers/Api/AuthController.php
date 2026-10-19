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
                return response()->json(['message' => 'Kredensial salah'], 401);
            }
            $wali = User::where('id', $student->parent_id)->where('role', 'wali_murid')->where('is_active', true)->first();
            if (! $wali || ! Hash::check($v['password'], $wali->password)) {
                return response()->json(['message' => 'Kredensial salah'], 401);
            }
            auth()->setUser($wali);

            return ['token' => $wali->createToken('api')->plainTextToken, 'user' => $this->userPayload($wali)];
        }
        $attempt = ['email' => $identity, 'password' => $v['password']];
        if (! auth()->attempt($attempt)) {
            return response()->json(['message' => 'Kredensial salah'], 401);
        }
        $user = $request->user();
        if (! $user->is_active) {
            auth()->logout();

            return response()->json(['message' => 'Akun nonaktif'], 403);
        }

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
