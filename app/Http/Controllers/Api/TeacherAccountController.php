<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\User;
use App\Services\TeacherProfileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TeacherAccountController extends Controller
{
    public function __construct(protected TeacherProfileService $teacherProfiles) {}

    public function index(Request $request)
    {
        $perPage = min(max((int) $request->query('per_page', 50), 1), 200);

        return User::query()
            ->whereIn('role', [User::ROLE_GURU, User::ROLE_PENDING])
            ->select(['id', 'name', 'email', 'phone', 'role', 'unit', 'is_active', 'is_protected', 'created_at'])
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = trim((string) $request->query('search'));

                $query->where(fn ($searchQuery) => $searchQuery
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%"));
            })
            ->when($request->filled('role'), fn ($query) => $query->where('role', $request->query('role')))
            ->when($request->filled('is_active'), fn ($query) => $query->where(
                'is_active',
                filter_var($request->query('is_active'), FILTER_VALIDATE_BOOLEAN),
            ))
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function store(Request $request)
    {
        /** @var User $actor */
        $actor = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $role = $actor->isAdminUtama() ? User::ROLE_GURU : User::ROLE_PENDING;

        $account = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $role,
            'unit' => null,
            'is_active' => $data['is_active'] ?? true,
        ]);

        $this->teacherProfiles->syncFromUser($account);

        activity_log('user.create', $actor, User::class, $account->id, [
            'role' => $role,
            'source' => 'teachers',
        ]);

        return response()->json($this->payload($account), 201);
    }

    public function update(Request $request, int $id)
    {
        /** @var User $actor */
        $actor = $request->user();
        /** @var User $account */
        $account = User::query()
            ->whereIn('role', [User::ROLE_GURU, User::ROLE_PENDING])
            ->findOrFail($id);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('users', 'email')->ignore($account->id)],
            'password' => ['sometimes', 'nullable', 'string', 'min:6'],
            'role' => ['sometimes', Rule::in([User::ROLE_GURU, User::ROLE_PENDING])],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if (! $actor->isAdminUtama() && array_key_exists('role', $data)) {
            throw ValidationException::withMessages([
                'role' => 'Hanya Admin Utama yang dapat menetapkan role.',
            ]);
        }

        $passwordChanged = filled($data['password'] ?? null);

        $account->fill([
            'name' => $data['name'] ?? $account->name,
            'email' => $data['email'] ?? $account->email,
            'role' => $data['role'] ?? $account->role,
            'is_active' => $data['is_active'] ?? $account->is_active,
        ]);

        if ($passwordChanged) {
            $account->password = Hash::make($data['password']);
        }

        $account->save();

        if (in_array($account->role, [User::ROLE_GURU, User::ROLE_PENDING], true)) {
            $this->teacherProfiles->syncFromUser($account->load('units'));
        }

        activity_log('user.update', $actor, User::class, $account->id, [
            'role' => $account->role,
            'source' => 'teachers',
            'password_changed' => $passwordChanged,
        ]);

        return response()->json($this->payload($account->fresh()));
    }

    /**
     * Daftar nama kelas untuk kebutuhan halaman guru/operator. Hanya memuat
     * identitas kelas, tanpa data santri.
     */
    public function classrooms()
    {
        return Classroom::query()
            ->orderBy('grade')
            ->orderBy('name')
            ->get(['id', 'name', 'grade', 'academic_year', 'unit']);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(User $account): array
    {
        return $account->only([
            'id',
            'name',
            'email',
            'role',
            'unit',
            'is_active',
            'is_protected',
            'phone',
            'created_at',
        ]);
    }
}
