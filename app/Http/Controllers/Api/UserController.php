<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    private const ROLES = ['admin', 'guru', 'bendahara_lpi', 'bendahara_sma', 'wali_murid'];

    public function index(Request $request)
    {
        $perPage = min(max((int) $request->query('per_page', 50), 1), 200);

        $q = User::query()->select(['id', 'name', 'email', 'phone', 'role', 'unit', 'is_active', 'is_protected', 'created_at'])
            ->when($request->role, fn ($qq, $v) => $qq->where('role', $v))
            ->when($request->search, fn ($qq, $v) => $qq->where(fn ($q2) => $q2->where('name', 'like', "%{$v}%")->orWhere('email', 'like', "%{$v}%")))
            ->when($request->has('is_active'), fn ($qq) => $qq->where('is_active', (bool) $request->is_active));

        return $q->orderBy('name')->paginate($perPage);
    }

    public function stats()
    {
        $row = User::query()->selectRaw(
            'COUNT(*) as total, COALESCE(SUM(CASE WHEN role = ? THEN 1 ELSE 0 END), 0) as total_guru',
            [User::ROLE_GURU],
        )->first();

        return response()->json([
            'total' => (int) $row->total,
            'guru' => (int) $row->total_guru,
        ]);
    }

    public function store(Request $request)
    {
        $v = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:6',
            'role' => 'required|in:'.implode(',', self::ROLES),
            'unit' => 'nullable|in:lpi,sma',
            'is_active' => 'boolean',
        ]);

        if ($v['role'] === User::ROLE_ADMIN && ! $request->user()->isProtectedAdmin()) {
            abort(403, 'Hanya Admin Utama yang dapat membuat akun admin.');
        }

        $v['password'] = Hash::make($v['password']);
        $v['unit'] = $this->unitForRole($v['role'], $v['unit'] ?? null);
        $u = User::create($v);

        ActivityLogger::log('user.create', $request->user(), User::class, $u->id, [
            'role' => $u->role,
            'unit' => $u->unit,
        ]);

        return response()->json($u->only(['id', 'name', 'email', 'role', 'unit', 'is_active', 'is_protected', 'phone', 'created_at']), 201);
    }

    public function update(Request $request, int $id)
    {
        /** @var User $actor */
        $actor = $request->user();
        $u = User::findOrFail($id);

        if ($u->isProtectedAdmin() && ! $actor->isProtectedAdmin()) {
            abort(403, 'Akun Admin Utama hanya dapat diubah oleh Admin Utama.');
        }

        $v = $request->validate([
            'name' => 'sometimes|string',
            'email' => 'sometimes|email|unique:users,email,'.$id,
            'password' => 'sometimes|nullable|string|min:6',
            'role' => 'sometimes|in:'.implode(',', self::ROLES),
            'unit' => 'nullable|in:lpi,sma',
            'is_active' => 'sometimes|boolean',
        ]);

        if (isset($v['role']) || array_key_exists('unit', $v)) {
            $v['unit'] = $this->unitForRole($v['role'] ?? $u->role, $v['unit'] ?? $u->unit);
        }

        $newRole = $v['role'] ?? $u->role;
        $newUnit = $v['unit'] ?? $u->unit;

        if (! $actor->isProtectedAdmin()) {
            if ($newRole !== $u->role) {
                abort(403, 'Hanya Admin Utama yang dapat mengubah role akun.');
            }
            if ($newUnit !== $u->unit) {
                abort(403, 'Hanya Admin Utama yang dapat mengubah unit akun.');
            }
        }

        if ($u->isProtectedAdmin() && ($newRole !== User::ROLE_ADMIN || $newUnit !== $u->unit)) {
            abort(403, 'Role dan unit Admin Utama tidak dapat diubah.');
        }

        if (array_key_exists('is_active', $v) && $v['is_active'] === false) {
            if ($u->is_protected) {
                abort(403, 'Akun utama tidak dapat dinonaktifkan');
            }
            if ((int) $u->id === (int) $actor->id) {
                abort(403, 'Tidak dapat menonaktifkan akun sendiri');
            }
            if ($u->role === User::ROLE_ADMIN && User::where('role', User::ROLE_ADMIN)->where('is_active', true)->where('id', '!=', $u->id)->count() === 0) {
                abort(409, 'Minimal satu admin aktif harus tersisa');
            }
        }

        if ($u->role === User::ROLE_ADMIN && $newRole !== User::ROLE_ADMIN) {
            if (User::where('role', User::ROLE_ADMIN)->where('is_active', true)->where('id', '!=', $u->id)->count() === 0) {
                abort(409, 'Minimal satu admin aktif harus tersisa');
            }
        }

        if (isset($v['password']) && $v['password']) {
            $v['password'] = Hash::make($v['password']);
        } else {
            unset($v['password']);
        }
        $u->update($v);

        return $u->fresh()->only(['id', 'name', 'email', 'role', 'unit', 'is_active', 'is_protected', 'phone', 'created_at']);
    }

    public function destroy(Request $request, int $id)
    {
        /** @var User $actor */
        $actor = $request->user();
        $u = User::findOrFail($id);

        if ($u->is_protected) {
            abort(403, 'Akun utama tidak dapat dihapus');
        }
        if ((int) $u->id === (int) $actor->id) {
            abort(403, 'Tidak dapat menghapus akun sendiri');
        }
        if ($u->role === User::ROLE_ADMIN && ! $actor->isProtectedAdmin()) {
            abort(403, 'Hanya Admin Utama yang dapat menghapus akun admin.');
        }
        if ($u->role === User::ROLE_ADMIN && User::where('role', User::ROLE_ADMIN)->where('id', '!=', $u->id)->count() === 0) {
            abort(409, 'Minimal satu admin harus tersisa');
        }
        if (Student::where('parent_id', $u->id)->exists()) {
            abort(409, 'Akun masih terhubung dengan data santri');
        }
        $u->delete();
        ActivityLogger::log('user.delete', $actor, User::class, $id, ['deleted_id' => $id]);

        return response()->noContent();
    }

    /**
     * Unit akun mengikuti peran untuk bendahara. Peran lain boleh memiliki
     * unit eksplisit agar izin lintas modul tetap terbatas pada unit tersebut.
     */
    private function unitForRole(string $role, ?string $unit): ?string
    {
        return match ($role) {
            User::ROLE_BENDAHARA_LPI => 'lpi',
            User::ROLE_BENDAHARA_SMA => 'sma',
            default => $unit,
        };
    }
}
