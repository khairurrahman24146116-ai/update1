<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserPermission;
use App\Services\ActivityLogger;
use App\Support\PermissionCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PermissionController extends Controller
{
    public function catalog()
    {
        return response()->json([
            'modules' => PermissionCatalog::modules(),
            'privileged' => PermissionCatalog::PRIVILEGED,
            'roles' => [
                'admin' => 'Admin',
                'guru' => 'Guru',
                'bendahara_lpi' => 'Bendahara LPI',
                'bendahara_sma' => 'Bendahara SMA',
                'wali_murid' => 'Wali Murid',
            ],
        ]);
    }

    public function show(Request $request, int $id)
    {
        $target = User::findOrFail($id);

        return response()->json($this->payload($target, $request->user()));
    }

    public function update(Request $request, int $id)
    {
        /** @var User $actor */
        $actor = $request->user();
        $target = User::findOrFail($id);

        $this->assertCanManage($actor, $target);

        $data = $request->validate([
            'allow' => ['sometimes', 'array'],
            'allow.*' => ['string', 'distinct', Rule::in(PermissionCatalog::allPermissions())],
            'deny' => ['sometimes', 'array'],
            'deny.*' => ['string', 'distinct', Rule::in(PermissionCatalog::allPermissions())],
        ]);

        $allow = array_values(array_unique($data['allow'] ?? []));
        $deny = array_values(array_unique($data['deny'] ?? []));

        $overlap = array_values(array_intersect($allow, $deny));
        if ($overlap !== []) {
            throw ValidationException::withMessages([
                'allow' => 'Izin tidak boleh sekaligus diberikan dan dicabut: '.implode(', ', $overlap),
            ]);
        }

        $this->assertAssignable($actor, $target, $allow);
        $this->assertAssignable($actor, $target, $deny);

        if ($deny !== [] && $actor->isProtectedAdmin() === false && ! $actor->hasPermission('permissions.manage')) {
            abort(403, 'Anda tidak berwenang mencabut izin.');
        }

        $this->assertUnitAvailable($target, $allow);
        $this->assertNoLockout($target, $allow, $deny);

        DB::transaction(function () use ($target, $allow, $deny, $actor) {
            $target->permissionOverrides()->delete();

            $rows = [];
            foreach ($allow as $permission) {
                $rows[] = ['permission' => $permission, 'effect' => UserPermission::EFFECT_ALLOW];
            }
            foreach ($deny as $permission) {
                $rows[] = ['permission' => $permission, 'effect' => UserPermission::EFFECT_DENY];
            }

            foreach ($rows as $row) {
                $target->permissionOverrides()->create([
                    ...$row,
                    'granted_by' => $actor->id,
                ]);
            }
        });

        $target->unsetRelation('permissionOverrides');
        $target->refresh();
        $target->forgetPermissionCache();

        ActivityLogger::log('permission.update', $actor, User::class, $target->id, [
            'allow' => $allow,
            'deny' => $deny,
            'role' => $target->role,
        ]);

        return response()->json($this->payload($target, $actor));
    }

    private function payload(User $target, User $actor): array
    {
        $overrides = $target->permissionOverridesMap();
        $allow = [];
        $deny = [];

        foreach ($overrides as $permission => $effect) {
            if ($effect === UserPermission::EFFECT_ALLOW) {
                $allow[] = $permission;
            } else {
                $deny[] = $permission;
            }
        }

        sort($allow);
        sort($deny);

        $reason = null;
        if ((int) $actor->id === (int) $target->id) {
            $reason = 'Tidak dapat mengubah hak akses akun sendiri.';
        } elseif ($target->isProtectedAdmin()) {
            $reason = 'Hak akses Admin Utama tidak dapat diubah.';
        } elseif (! $actor->hasPermission('permissions.manage')) {
            $reason = 'Akun ini hanya dapat melihat hak akses.';
        }

        return [
            'user' => $target->only(['id', 'name', 'email', 'phone', 'role', 'unit', 'is_active', 'is_protected']),
            'role' => $target->role,
            'role_permissions' => $target->rolePermissions(),
            'allow' => $allow,
            'deny' => $deny,
            'effective_permissions' => $target->effectivePermissions(),
            'is_protected_admin' => $target->isProtectedAdmin(),
            'can_manage' => $reason === null,
            'cannot_manage_reason' => $reason,
            'modules' => PermissionCatalog::modules(),
            'privileged' => PermissionCatalog::PRIVILEGED,
        ];
    }

    private function assertCanManage(User $actor, User $target): void
    {
        if (! $actor->hasPermission('permissions.manage')) {
            abort(403, 'Anda tidak memiliki izin mengelola hak akses.');
        }

        if (! $actor->isAdmin()) {
            abort(403, 'Hanya admin yang dapat mengelola hak akses.');
        }

        if ((int) $actor->id === (int) $target->id) {
            abort(403, 'Tidak dapat mengubah hak akses akun sendiri.');
        }

        if ($target->isProtectedAdmin()) {
            abort(403, 'Hak akses Admin Utama tidak dapat diubah.');
        }
    }

    /**
     * Izin sensitif hanya untuk admin, dan hanya Admin Utama yang boleh
     * memberikannya. Pengelola tidak dapat memberi izin yang tidak dimilikinya.
     *
     * @param  list<string>  $permissions
     */
    private function assertAssignable(User $actor, User $target, array $permissions): void
    {
        foreach ($permissions as $permission) {
            if (! PermissionCatalog::isPrivileged($permission)) {
                continue;
            }

            if (! $target->isAdmin()) {
                abort(422, "Izin sensitif {$permission} tidak dapat diberikan kepada akun non-admin.");
            }

            if (! $actor->isProtectedAdmin()) {
                abort(403, "Hanya Admin Utama yang dapat mengatur izin sensitif {$permission}.");
            }
        }
    }

    /**
     * Pastikan akun non-admin yang menerima izin keuangan punya unit yang jelas.
     *
     * @param  list<string>  $permissions
     */
    private function assertUnitAvailable(User $target, array $permissions): void
    {
        if ($target->isAdmin() || $target->isFinanceRole()) {
            return;
        }

        $financePermissions = array_filter(
            $permissions,
            fn (string $permission) => str_starts_with($permission, 'finance.')
                || str_starts_with($permission, 'spp.')
                || str_starts_with($permission, 'reports.finance'),
        );

        if ($financePermissions !== [] && ! in_array($target->unit, ['lpi', 'sma'], true)) {
            throw ValidationException::withMessages([
                'allow' => 'Tetapkan unit LPI/SMA pada akun sebelum memberikan izin keuangan.',
            ]);
        }
    }

    /**
     * Jangan sampai tidak ada admin aktif yang masih dapat mengelola hak akses.
     *
     * @param  list<string>  $allow
     * @param  list<string>  $deny
     */
    private function assertNoLockout(User $target, array $allow, array $deny): void
    {
        if (! $target->isAdmin()) {
            return;
        }

        if (in_array('permissions.manage', $allow, true)) {
            return;
        }

        $stillManaging = $target->is_active
            && ! in_array('permissions.manage', $deny, true)
            && (in_array('permissions.manage', PermissionCatalog::forRole($target->role), true)
                || in_array('permissions.manage', $allow, true));

        $otherManager = User::query()
            ->where('role', User::ROLE_ADMIN)
            ->where('is_active', true)
            ->where('id', '!=', $target->id)
            ->where(function ($query) {
                $query->where('is_protected', true)
                    ->orWhereHas('permissionOverrides', fn ($q) => $q
                        ->where('permission', 'permissions.manage')
                        ->where('effect', UserPermission::EFFECT_ALLOW));
            })
            ->exists();

        $protectedExists = User::where('role', User::ROLE_ADMIN)
            ->where('is_active', true)
            ->where('is_protected', true)
            ->where('id', '!=', $target->id)
            ->exists();

        if (! $stillManaging && ! $otherManager && ! $protectedExists) {
            abort(409, 'Minimal satu admin aktif harus tetap dapat mengelola hak akses.');
        }
    }
}
