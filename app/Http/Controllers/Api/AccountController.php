<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Unit;
use App\Models\User;
use App\Services\TeacherProfileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AccountController extends Controller
{
    private const MANAGEABLE_ROLES = [
        User::ROLE_KEPALA_SEKOLAH,
        User::ROLE_BENDAHARA,
        User::ROLE_BENDAHARA_LPI,
        User::ROLE_BENDAHARA_SMA,
        User::ROLE_GURU,
        User::ROLE_WALI_MURID,
        User::ROLE_OPERATOR,
    ];

    public function __construct(protected TeacherProfileService $teacherProfiles) {}

    private const ROLE_LABELS = [
        'admin' => 'Admin',
        'admin_utama' => 'Admin Utama',
        'operator' => 'Operator',
        'pending' => 'Menunggu Hak Akses',
        'kepala_sekolah' => 'Kepala Sekolah',
        'bendahara' => 'Bendahara',
        'bendahara_lpi' => 'Bendahara LPI',
        'bendahara_sma' => 'Bendahara SMA',
        'guru' => 'Guru',
        'wali_murid' => 'Wali Murid',
    ];

    public function index(Request $request)
    {
        $this->authorize('viewAny', User::class);

        /** @var User $actor */
        $actor = $request->user();
        $perPage = min(max((int) $request->query('per_page', 20), 1), 100);
        $unit = strtolower(trim((string) $request->query('unit', '')));

        $accounts = User::query()
            ->when($actor->isOperator(), fn ($query) => $query->whereIn('role', [
                User::ROLE_GURU,
                User::ROLE_PENDING,
            ]))
            ->with('units')
            ->when($unit !== '' && $unit !== 'all', function ($query) use ($unit): void {
                $query->whereHas('units', fn ($units) => $units->whereRaw('LOWER(units.kode) = ?', [$unit]));
            })
            ->when($request->filled('role'), fn ($query) => $query->where('role', $request->query('role')))
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = trim((string) $request->query('search'));

                $query->where(fn ($searchQuery) => $searchQuery
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%"));
            })
            ->when($request->filled('is_active'), fn ($query) => $query->where(
                'is_active',
                filter_var($request->query('is_active'), FILTER_VALIDATE_BOOLEAN),
            ))
            ->orderBy('name')
            ->paginate($perPage)
            ->through(fn (User $account): array => $this->payload($account));

        return response()->json($accounts);
    }

    public function options(Request $request)
    {
        $this->authorize('viewAny', User::class);

        /** @var User $actor */
        $actor = $request->user();
        $roles = $actor->isAdminUtama()
            ? collect(self::MANAGEABLE_ROLES)
            : collect([User::ROLE_GURU, User::ROLE_PENDING]);

        return response()->json([
            'roles' => $roles
                ->map(fn (string $role): array => [
                    'value' => $role,
                    'label' => self::ROLE_LABELS[$role] ?? $role,
                ])
                ->values(),
            'units' => Unit::query()
                ->orderBy('kode')
                ->get(['kode', 'nama'])
                ->map(fn (Unit $unit): array => [
                    'code' => strtoupper($unit->kode),
                    'label' => $unit->nama,
                ])
                ->values(),
            'can_assign_role' => $actor->isAdminUtama(),
            'can_assign_unit' => $actor->isAdminUtama(),
            'can_manage_permissions' => $actor->isAdminUtama() && $actor->hasPermission('permissions.manage'),
            'current_user_id' => $actor->id,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', User::class);

        /** @var User $actor */
        $actor = $request->user();

        if ($actor->isOperator()) {
            return $this->storePendingTeacher($request, $actor);
        }

        $this->normalizeUnitInput($request);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'role' => ['required', Rule::in(self::MANAGEABLE_ROLES)],
            'units' => ['required', 'array', 'min:1'],
            'units.*.code' => ['required', 'string', 'distinct', Rule::in(['LPI', 'SMA', 'SMP'])],
            'units.*.jabatan' => ['nullable', 'string', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $units = $this->normalizeUnitsForRole($data['role'], $data['units']);

        $account = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
            'unit' => strtolower($units[0]['code']),
            'is_active' => $data['is_active'] ?? true,
        ]);

        $this->syncUnits($account, $units);

        if (in_array($account->role, [User::ROLE_GURU, User::ROLE_PENDING], true)) {
            $this->teacherProfiles->syncFromUser($account->load('units'));
        }

        activity_log('user.create', $actor, User::class, $account->id, [
            'role' => $account->role,
            'units' => collect($units)->pluck('code')->all(),
        ]);

        return response()->json($this->payload($account->load('units')), 201);
    }

    public function update(Request $request, int $id)
    {
        /** @var User $actor */
        $actor = $request->user();
        /** @var User $account */
        $account = User::with('units')->findOrFail($id);

        $this->authorize('update', $account);

        if ($actor->isOperator()) {
            return $this->updateAsOperator($request, $actor, $account);
        }

        $this->normalizeUnitInput($request);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('users', 'email')->ignore($account->id)],
            'role' => ['sometimes', Rule::in(self::MANAGEABLE_ROLES)],
            'units' => ['sometimes', 'array', 'min:1'],
            'units.*.code' => ['required', 'string', 'distinct', Rule::in(['LPI', 'SMA', 'SMP'])],
            'units.*.jabatan' => ['nullable', 'string', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $newRole = $data['role'] ?? $account->role;
        $units = array_key_exists('units', $data)
            ? $data['units']
            : $this->unitsFromAccount($account);

        if ($units === [] && $newRole !== User::ROLE_PENDING) {
            throw ValidationException::withMessages([
                'units' => 'Minimal satu unit wajib dipilih.',
            ]);
        }

        $units = $this->normalizeUnitsForRole($newRole, $units);
        $before = [
            'role' => $account->role,
            'unit' => $account->unit,
            'is_active' => $account->is_active,
            'units' => $this->unitsFromAccount($account),
        ];

        $account->fill([
            'name' => $data['name'] ?? $account->name,
            'email' => $data['email'] ?? $account->email,
            'role' => $newRole,
            'unit' => $units === [] ? null : strtolower($units[0]['code']),
            'is_active' => $data['is_active'] ?? $account->is_active,
        ])->save();

        $this->syncUnits($account, $units);

        if (in_array($account->role, [User::ROLE_GURU, User::ROLE_PENDING], true)) {
            $this->teacherProfiles->syncFromUser($account->load('units'));
        }

        $action = 'user.update';
        if (array_keys($data) === ['is_active']) {
            $action = $data['is_active'] ? 'user.activate' : 'user.deactivate';
        }

        activity_log($action, $actor, User::class, $account->id, [
            'role' => ['from' => $before['role'], 'to' => $newRole],
            'unit' => ['from' => $before['unit'], 'to' => $account->unit],
            'is_active' => ['from' => $before['is_active'], 'to' => $account->is_active],
            'units' => [
                'from' => collect($before['units'])->pluck('code')->all(),
                'to' => collect($units)->pluck('code')->all(),
            ],
        ]);

        return response()->json($this->payload($account->fresh('units')));
    }

    public function resetPassword(Request $request, int $id)
    {
        /** @var User $account */
        $account = User::findOrFail($id);

        $this->authorize('update', $account);

        $data = $request->validate([
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $account->forceFill([
            'password' => Hash::make($data['password']),
        ])->save();

        $account->tokens()->delete();

        activity_log('user.password_reset', $request->user(), User::class, $account->id);

        return response()->noContent();
    }

    private function storePendingTeacher(Request $request, User $actor)
    {
        if ($request->hasAny(['role', 'units'])) {
            throw ValidationException::withMessages([
                'role' => 'Hanya Admin Utama yang dapat menetapkan role, unit, dan jabatan.',
            ]);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $account = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => User::ROLE_PENDING,
            'unit' => null,
            'is_active' => $data['is_active'] ?? true,
        ]);

        $this->teacherProfiles->syncFromUser($account);

        activity_log('user.create', $actor, User::class, $account->id, [
            'role' => User::ROLE_PENDING,
            'source' => 'operator',
        ]);

        return response()->json($this->payload($account->load('units')), 201);
    }

    private function updateAsOperator(Request $request, User $actor, User $account)
    {
        if ($request->hasAny(['role', 'units'])) {
            throw ValidationException::withMessages([
                'role' => 'Hanya Admin Utama yang dapat menetapkan role, unit, dan jabatan.',
            ]);
        }

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('users', 'email')->ignore($account->id)],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $before = [
            'is_active' => $account->is_active,
        ];

        $account->fill([
            'name' => $data['name'] ?? $account->name,
            'email' => $data['email'] ?? $account->email,
            'is_active' => $data['is_active'] ?? $account->is_active,
        ])->save();

        if (in_array($account->role, [User::ROLE_GURU, User::ROLE_PENDING], true)) {
            $this->teacherProfiles->syncFromUser($account->load('units'));
        }

        $action = 'user.update';
        if (array_keys($data) === ['is_active']) {
            $action = $data['is_active'] ? 'user.activate' : 'user.deactivate';
        }

        activity_log($action, $actor, User::class, $account->id, [
            'is_active' => ['from' => $before['is_active'], 'to' => $account->is_active],
            'source' => 'operator',
        ]);

        return response()->json($this->payload($account->fresh('units')));
    }

    private function normalizeUnitInput(Request $request): void
    {
        $units = $request->input('units');

        if (! is_array($units)) {
            return;
        }

        $request->merge([
            'units' => collect($units)
                ->map(function (mixed $unit): array {
                    if (is_string($unit)) {
                        return ['code' => strtoupper(trim($unit)), 'jabatan' => null];
                    }

                    $code = is_array($unit) ? ($unit['code'] ?? $unit['kode'] ?? '') : '';

                    return [
                        'code' => strtoupper(trim((string) $code)),
                        'jabatan' => is_array($unit) ? ($unit['jabatan'] ?? null) : null,
                    ];
                })
                ->all(),
        ]);
    }

    /**
     * @param  array<int, array{code: string, jabatan: string|null}>  $units
     * @return array<int, array{code: string, jabatan: string|null}>
     */
    private function normalizeUnitsForRole(string $role, array $units): array
    {
        $normalized = collect($units)
            ->map(fn (array $unit): array => [
                'code' => strtoupper($unit['code']),
                'jabatan' => filled($unit['jabatan'] ?? null) ? trim($unit['jabatan']) : null,
            ])
            ->unique('code')
            ->values();

        $forcedUnit = match ($role) {
            User::ROLE_BENDAHARA_LPI => 'LPI',
            User::ROLE_BENDAHARA_SMA => 'SMA',
            default => null,
        };

        if ($forcedUnit === null) {
            return $normalized->all();
        }

        $selected = $normalized->firstWhere('code', $forcedUnit);

        return [[
            'code' => $forcedUnit,
            'jabatan' => $selected['jabatan'] ?? null,
        ]];
    }

    /**
     * @param  array<int, array{code: string, jabatan: string|null}>  $units
     */
    private function syncUnits(User $account, array $units): void
    {
        $unitIds = Unit::query()
            ->whereIn('kode', collect($units)->pluck('code')->all())
            ->get(['id', 'kode'])
            ->mapWithKeys(fn (Unit $unit): array => [strtoupper($unit->kode) => $unit->id]);

        $account->units()->sync(
            collect($units)
                ->mapWithKeys(function (array $unit) use ($unitIds): array {
                    $unitId = $unitIds[strtoupper($unit['code'])] ?? null;

                    return $unitId === null ? [] : [$unitId => ['jabatan' => $unit['jabatan']]];
                })
                ->all(),
        );
    }

    /**
     * @return array<int, array{code: string, jabatan: string|null}>
     */
    private function unitsFromAccount(User $account): array
    {
        return $account->units
            ->sortBy('kode')
            ->map(fn (Unit $unit): array => [
                'code' => strtoupper($unit->kode),
                'jabatan' => $unit->pivot->jabatan,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(User $account): array
    {
        $account->loadMissing('units');

        return [
            'id' => $account->id,
            'name' => $account->name,
            'email' => $account->email,
            'phone' => $account->phone,
            'role' => $account->role,
            'role_label' => self::ROLE_LABELS[$account->role] ?? $account->role,
            'unit' => $account->unit,
            'is_active' => $account->is_active,
            'is_protected' => $account->is_protected,
            'created_at' => $account->created_at?->toISOString(),
            'units' => $account->units
                ->sortBy('kode')
                ->map(fn (Unit $unit): array => [
                    'code' => strtoupper($unit->kode),
                    'label' => $unit->nama,
                    'jabatan' => $unit->pivot->jabatan,
                ])
                ->values()
                ->all(),
        ];
    }
}
