<?php

namespace Tests\Feature\Api;

use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class OperatorAccountApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_operator_creates_pending_account_without_role_or_units(): void
    {
        $operator = $this->operator();

        $response = $this->actingAs($operator)->postJson('/api/accounts', [
            'name' => 'Ustadz Baru',
            'email' => 'pending@madani.test',
            'password' => 'password123',
            'is_active' => true,
        ])->assertCreated()
            ->assertJsonPath('role', User::ROLE_PENDING)
            ->assertJsonPath('unit', null)
            ->assertJsonCount(0, 'units');

        $account = User::where('email', 'pending@madani.test')->firstOrFail();

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'user.create',
            'target_type' => User::class,
            'target_id' => $account->id,
        ]);
        $this->assertArrayNotHasKey('password', $response->json());
    }

    public function test_operator_cannot_set_role_or_units(): void
    {
        $this->actingAs($this->operator())->postJson('/api/accounts', [
            'name' => 'Ustadz Multi Unit',
            'email' => 'multi@madani.test',
            'password' => 'password123',
            'role' => 'guru',
            'units' => [['code' => 'LPI', 'jabatan' => 'Wali Kelas']],
        ])->assertStatus(422)
            ->assertJsonValidationErrors('role');

        $this->assertDatabaseMissing('users', ['email' => 'multi@madani.test']);
    }

    public function test_operator_only_lists_guru_and_pending_accounts(): void
    {
        $operator = $this->operator();
        User::factory()->create(['role' => 'admin_utama', 'name' => 'Admin Tersembunyi']);
        User::factory()->create(['role' => 'admin', 'name' => 'Admin Lama']);
        User::factory()->create(['role' => 'operator', 'name' => 'Operator Lain']);
        User::factory()->create(['role' => 'wali_murid', 'name' => 'Wali Tersembunyi']);
        $this->accountWithUnits(['name' => 'Guru Tampil', 'role' => 'guru'], ['LPI']);
        User::factory()->create(['name' => 'Pending Tampil', 'role' => 'pending']);

        $response = $this->actingAs($operator)->getJson('/api/accounts')->assertOk();

        $names = collect($response->json('data'))->pluck('name')->sort()->values()->all();

        $this->assertSame(['Guru Tampil', 'Pending Tampil'], $names);
    }

    public function test_operator_cannot_update_role_or_units(): void
    {
        $operator = $this->operator();
        $account = $this->accountWithUnits([
            'name' => 'Guru Unit',
            'email' => 'guru-unit@madani.test',
            'role' => 'guru',
        ], ['LPI']);

        $this->actingAs($operator)->putJson("/api/accounts/{$account->id}", [
            'units' => [['code' => 'SMP', 'jabatan' => 'Guru']],
        ])->assertStatus(422)
            ->assertJsonValidationErrors('role');

        $this->assertDatabaseMissing('user_unit', [
            'user_id' => $account->id,
            'unit_id' => $this->unitId('SMP'),
        ]);
    }

    public function test_operator_can_rename_and_deactivate_until_login_is_blocked(): void
    {
        $operator = $this->operator();
        $account = $this->accountWithUnits([
            'name' => 'Guru Nonaktif',
            'email' => 'nonaktif-operator@madani.test',
            'role' => 'guru',
        ], ['LPI']);

        $this->actingAs($operator)->putJson("/api/accounts/{$account->id}", [
            'name' => 'Guru Diperbarui',
        ])->assertOk()
            ->assertJsonPath('name', 'Guru Diperbarui');

        $this->actingAs($operator)
            ->putJson("/api/accounts/{$account->id}", ['is_active' => false])
            ->assertOk()
            ->assertJsonPath('is_active', false);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'user.deactivate',
            'target_id' => $account->id,
        ]);

        $this->postJson('/api/login', [
            'identity' => 'nonaktif-operator@madani.test',
            'password' => 'password',
        ])->assertStatus(403)
            ->assertJsonPath('code', 'account_inactive');
    }

    public function test_operator_can_activate_account_without_units(): void
    {
        $operator = $this->operator();
        $account = User::factory()->create([
            'name' => 'Akun Lama',
            'email' => 'akun-lama@madani.test',
            'role' => 'guru',
            'unit' => null,
            'is_active' => false,
        ]);

        $this->actingAs($operator)
            ->putJson("/api/accounts/{$account->id}", ['is_active' => true])
            ->assertOk()
            ->assertJsonPath('is_active', true)
            ->assertJsonCount(0, 'units');
    }

    public function test_operator_can_reset_password_and_revoke_sessions(): void
    {
        $operator = $this->operator();
        $account = $this->accountWithUnits([
            'name' => 'Guru Reset',
            'email' => 'reset@madani.test',
            'role' => 'guru',
        ], ['SMA']);
        $account->createToken('test-session');

        $this->actingAs($operator)->postJson("/api/accounts/{$account->id}/reset-password", [
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertNoContent();

        $account->refresh();

        $this->assertTrue(Hash::check('new-password', $account->password));
        $this->assertDatabaseCount('personal_access_tokens', 0);
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'user.password_reset',
            'target_id' => $account->id,
        ]);
    }

    public function test_operator_cannot_update_or_reset_self_or_another_operator(): void
    {
        $operator = $this->operator();
        $otherOperator = User::factory()->create(['role' => 'operator']);

        foreach ([$operator, $otherOperator] as $target) {
            $this->actingAs($operator)
                ->putJson("/api/accounts/{$target->id}", ['name' => 'Tidak Boleh'])
                ->assertStatus(403);

            $this->actingAs($operator)
                ->postJson("/api/accounts/{$target->id}/reset-password", [
                    'password' => 'new-password',
                    'password_confirmation' => 'new-password',
                ])
                ->assertStatus(403);
        }
    }

    public function test_admin_utama_assigns_role_and_units_to_pending_account(): void
    {
        $admin = $this->admin();
        $pending = User::factory()->create([
            'name' => 'Guru Pending',
            'email' => 'guru-pending@madani.test',
            'role' => 'pending',
            'unit' => null,
        ]);

        $this->actingAs($admin)->putJson("/api/accounts/{$pending->id}", [
            'role' => 'guru',
            'units' => [
                ['code' => 'LPI', 'jabatan' => 'Wali Kelas'],
                ['code' => 'SMA', 'jabatan' => 'Guru Mapel'],
            ],
        ])->assertOk()
            ->assertJsonPath('role', 'guru')
            ->assertJsonPath('unit', 'lpi')
            ->assertJsonCount(2, 'units');

        $this->assertDatabaseHas('user_unit', [
            'user_id' => $pending->id,
            'unit_id' => $this->unitId('SMA'),
            'jabatan' => 'Guru Mapel',
        ]);
    }

    public function test_admin_utama_can_create_full_account_and_finance_unit_is_forced(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->postJson('/api/accounts', [
            'name' => 'Bendahara LPI',
            'email' => 'bendahara-lpi@madani.test',
            'password' => 'password123',
            'role' => 'bendahara_lpi',
            'units' => [
                ['code' => 'SMA', 'jabatan' => 'Tidak Dipakai'],
                ['code' => 'LPI', 'jabatan' => 'Bendahara LPI'],
            ],
        ])->assertCreated()
            ->assertJsonPath('role', 'bendahara_lpi')
            ->assertJsonPath('unit', 'lpi')
            ->assertJsonCount(1, 'units')
            ->assertJsonPath('units.0.code', 'LPI');
    }

    public function test_admin_utama_cannot_create_or_modify_admin_utama_accounts(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->postJson('/api/accounts', [
            'name' => 'Admin Kedua',
            'email' => 'admin-kedua@madani.test',
            'password' => 'password123',
            'role' => 'admin_utama',
            'units' => [['code' => 'LPI', 'jabatan' => 'Admin']],
        ])->assertStatus(422)
            ->assertJsonValidationErrors('role');

        $other = User::factory()->create(['role' => 'admin_utama', 'is_protected' => false]);

        $this->actingAs($admin)->putJson("/api/accounts/{$other->id}", [
            'name' => 'Diubah',
        ])->assertStatus(403);
    }

    public function test_non_operator_roles_cannot_access_account_management(): void
    {
        foreach (['kepala_sekolah', 'bendahara', 'bendahara_lpi', 'bendahara_sma', 'guru', 'admin'] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)->getJson('/api/accounts')->assertStatus(403);
            $this->actingAs($user)->postJson('/api/accounts', [
                'name' => 'Akun Baru',
                'email' => "{$role}-baru@madani.test",
                'password' => 'password123',
            ])->assertStatus(403);
        }
    }

    public function test_operator_creates_pending_teacher_and_admin_utama_creates_guru(): void
    {
        $operator = $this->operator();
        $admin = $this->admin();

        $this->actingAs($operator)->postJson('/api/teacher-accounts', [
            'name' => 'Guru Dari Operator',
            'email' => 'guru-operator@madani.test',
            'password' => 'password123',
            'is_active' => true,
        ])->assertCreated()
            ->assertJsonPath('role', 'pending');

        $this->actingAs($admin)->postJson('/api/teacher-accounts', [
            'name' => 'Guru Dari Admin',
            'email' => 'guru-admin@madani.test',
            'password' => 'password123',
            'is_active' => true,
        ])->assertCreated()
            ->assertJsonPath('role', 'guru');
    }

    public function test_operator_cannot_set_role_through_teacher_endpoint(): void
    {
        $operator = $this->operator();
        $pending = User::factory()->create([
            'name' => 'Pending Guru',
            'email' => 'pending-guru@madani.test',
            'role' => 'pending',
        ]);

        $this->actingAs($operator)->putJson("/api/teacher-accounts/{$pending->id}", [
            'role' => 'guru',
        ])->assertStatus(422)
            ->assertJsonValidationErrors('role');
    }

    public function test_pending_account_is_limited_to_identity_and_logout(): void
    {
        $pending = User::factory()->create([
            'name' => 'Akun Pending',
            'email' => 'pending-login@madani.test',
            'password' => 'password123',
            'role' => 'pending',
            'unit' => null,
        ]);

        $token = $this->postJson('/api/login', [
            'identity' => $pending->email,
            'password' => 'password123',
        ])->assertOk()
            ->json('token');

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('role', 'pending');

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/students')
            ->assertStatus(403)
            ->assertJsonPath('code', 'pending_role');

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/logout')
            ->assertNoContent();
    }

    public function test_operator_cannot_view_students_or_classrooms(): void
    {
        $operator = $this->operator();

        $this->actingAs($operator)->getJson('/api/students')->assertStatus(403);
        $this->actingAs($operator)->getJson('/api/classrooms')->assertStatus(403);
        $this->actingAs($operator)->getJson('/api/teacher-accounts/classrooms')->assertOk();
    }

    private function admin(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_ADMIN_UTAMA,
            'is_protected' => false,
        ]);
    }

    private function operator(): User
    {
        return User::factory()->create(['role' => 'operator']);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<int, string>  $codes
     */
    private function accountWithUnits(array $attributes, array $codes): User
    {
        $account = User::factory()->create(array_merge([
            'unit' => null,
        ], $attributes));

        $account->units()->attach(
            collect($codes)->mapWithKeys(fn (string $code): array => [
                $this->unitId($code) => ['jabatan' => null],
            ])->all(),
        );

        return $account;
    }

    private function unitId(string $code): int
    {
        return (int) Unit::where('kode', $code)->value('id');
    }
}
