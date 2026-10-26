<?php

namespace App\Models;

use App\Support\PermissionCatalog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';

    public const ROLE_ADMIN_UTAMA = 'admin_utama';

    public const ROLE_OPERATOR = 'operator';

    public const ROLE_KEPALA_SEKOLAH = 'kepala_sekolah';

    public const ROLE_BENDAHARA = 'bendahara';

    public const ROLE_BENDAHARA_LPI = 'bendahara_lpi';

    public const ROLE_BENDAHARA_SMA = 'bendahara_sma';

    public const ROLE_GURU = 'guru';

    public const ROLE_WALI_MURID = 'wali_murid';

    public const FINANCE_ROLES = [
        self::ROLE_BENDAHARA,
        self::ROLE_BENDAHARA_LPI,
        self::ROLE_BENDAHARA_SMA,
    ];

    protected $fillable = ['name', 'email', 'phone', 'password', 'role', 'unit', 'is_active', 'is_protected'];

    protected $hidden = ['password', 'remember_token'];

    /** @var list<string>|null */
    private ?array $effectivePermissionsCache = null;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'is_protected' => 'boolean',
        ];
    }

    public function permissionOverrides(): HasMany
    {
        return $this->hasMany(UserPermission::class);
    }

    public function units(): BelongsToMany
    {
        return $this->belongsToMany(Unit::class, 'user_unit')
            ->withPivot('jabatan')
            ->withTimestamps();
    }

    public function userUnits(): HasMany
    {
        return $this->hasMany(UserUnit::class);
    }

    public function teacher(): HasOne
    {
        return $this->hasOne(Teacher::class);
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, [self::ROLE_ADMIN, self::ROLE_ADMIN_UTAMA], true);
    }

    public function isOperator(): bool
    {
        return $this->role === self::ROLE_OPERATOR;
    }

    public function isKepalaSekolah(): bool
    {
        return $this->role === self::ROLE_KEPALA_SEKOLAH;
    }

    public function isBendahara(): bool
    {
        return $this->isFinanceRole();
    }

    public function isGuru(): bool
    {
        return $this->role === self::ROLE_GURU;
    }

    public function isWaliMurid(): bool
    {
        return $this->role === self::ROLE_WALI_MURID;
    }

    public function isAccountManager(): bool
    {
        return $this->isAdmin() || $this->isOperator();
    }

    /**
     * Unit data yang boleh diakses akun ini (huruf kecil).
     * Satu-satunya sumber adalah tabel user_unit.
     *
     * @return list<string>
     */
    public function effectiveUnitCodes(): array
    {
        return $this->userUnitCodes();
    }

    /**
     * @return list<string>
     */
    public function userUnitCodes(): array
    {
        return $this->units()
            ->pluck('units.kode')
            ->filter(fn ($code): bool => is_string($code) && trim($code) !== '')
            ->map(fn (string $code): string => strtolower(trim($code)))
            ->unique()
            ->values()
            ->all();
    }

    public function canAccessDataUnit(?string $unit): bool
    {
        if ($this->isAdmin() || $this->isOperator()) {
            return true;
        }

        if ($unit === null || trim($unit) === '') {
            return false;
        }

        return in_array(strtolower(trim($unit)), $this->effectiveUnitCodes(), true);
    }

    public function teachesClassroom(int|string|null $classroomId): bool
    {
        if ($classroomId === null) {
            return false;
        }

        if ($this->role !== self::ROLE_GURU) {
            return false;
        }

        return TeacherSubject::query()
            ->where('teacher_id', $this->id)
            ->where('classroom_id', $classroomId)
            ->exists()
            || Schedule::query()
                ->where('teacher_id', $this->id)
                ->where('classroom_id', $classroomId)
                ->exists();
    }

    public function canAccessLpiFinance(): bool
    {
        if ($this->isOperator()) {
            return false;
        }

        if ($this->isAdmin()) {
            return true;
        }

        return $this->hasPermission('finance.view') && $this->canAccessDataUnit('lpi');
    }

    /**
     * Admin Utama: akun admin yang ditandai proteksi sistem. Akun ini tidak
     * dapat dihapus, dinonaktifkan, atau diubah hak aksesnya, dan selalu
     * memiliki seluruh izin agar sistem tidak bisa terkunci.
     */
    public function isProtectedAdmin(): bool
    {
        return $this->isAdmin() && (bool) $this->is_protected;
    }

    public function isFinanceRole(): bool
    {
        return in_array($this->role, self::FINANCE_ROLES, true);
    }

    public function canAccessFinanceUnit(string $unit): bool
    {
        return $this->isAdmin() || $this->canAccessDataUnit($unit);
    }

    /**
     * Izin bawaan role, tanpa override akun.
     *
     * @return list<string>
     */
    public function rolePermissions(): array
    {
        if ($this->isProtectedAdmin()) {
            return PermissionCatalog::allPermissions();
        }

        return PermissionCatalog::forRole($this->role);
    }

    /**
     * Override akun: permission => 'allow'|'deny'.
     *
     * @return array<string, string>
     */
    public function permissionOverridesMap(): array
    {
        if ($this->relationLoaded('permissionOverrides')) {
            return $this->permissionOverrides
                ->mapWithKeys(fn (UserPermission $row) => [$row->permission => $row->effect])
                ->all();
        }

        return $this->permissionOverrides()
            ->pluck('effect', 'permission')
            ->all();
    }

    /**
     * Izin efektif: (izin role + allow) - deny. Deny selalu menang.
     *
     * @return list<string>
     */
    public function effectivePermissions(): array
    {
        if ($this->isProtectedAdmin()) {
            return PermissionCatalog::allPermissions();
        }

        if ($this->effectivePermissionsCache !== null) {
            return $this->effectivePermissionsCache;
        }

        $overrides = $this->permissionOverridesMap();
        $permissions = [];

        foreach (PermissionCatalog::allPermissions() as $permission) {
            $effect = $overrides[$permission] ?? null;

            if ($effect === UserPermission::EFFECT_DENY) {
                continue;
            }

            if (in_array($permission, PermissionCatalog::forRole($this->role), true) || $effect === UserPermission::EFFECT_ALLOW) {
                $permissions[] = $permission;
            }
        }

        return $this->effectivePermissionsCache = $permissions;
    }

    public function forgetPermissionCache(): void
    {
        $this->effectivePermissionsCache = null;
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->isProtectedAdmin()) {
            return true;
        }

        return in_array($permission, $this->effectivePermissions(), true);
    }

    /**
     * Akun non-admin tidak boleh memegang seluruh izin yang dapat diberikan.
     * Izin privileged sudah ditolak terpisah, jadi batasnya adalah seluruh
     * izin non-privileged di katalog.
     */
    public function hasFullPermissionAccess(): bool
    {
        if ($this->isAdmin()) {
            return false;
        }

        $assignable = array_values(array_filter(
            PermissionCatalog::allPermissions(),
            fn (string $permission): bool => ! PermissionCatalog::isPrivileged($permission),
        ));

        return array_diff($assignable, $this->effectivePermissions()) === [];
    }

    /**
     * @param  list<string>  $permissions
     */
    public function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Ringkasan sumber izin untuk UI: role|allow|deny|none.
     */
    public function permissionSource(string $permission): string
    {
        if ($this->isProtectedAdmin()) {
            return 'role';
        }

        $effect = $this->permissionOverridesMap()[$permission] ?? null;

        if ($effect === UserPermission::EFFECT_ALLOW) {
            return 'allow';
        }

        if ($effect === UserPermission::EFFECT_DENY) {
            return 'deny';
        }

        return in_array($permission, PermissionCatalog::forRole($this->role), true) ? 'role' : 'none';
    }

    public function permissionPayload(): array
    {
        return [
            'permissions' => $this->effectivePermissions(),
            'permission_labels' => PermissionCatalog::labels(),
            'role_permissions' => PermissionCatalog::forRole($this->role),
            'permission_overrides' => $this->permissionOverridesMap(),
            'has_full_permission_access' => $this->hasFullPermissionAccess(),
            'is_protected_admin' => $this->isProtectedAdmin(),
        ];
    }
}
