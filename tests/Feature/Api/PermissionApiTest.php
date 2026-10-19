<?php

namespace Tests\Feature\Api;

use App\Models\Classroom;
use App\Models\FinanceTransaction;
use App\Models\User;
use App\Models\UserPermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionApiTest extends TestCase
{
    use RefreshDatabase;

    private function protectedAdmin(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'is_protected' => true,
        ]);
    }

    public function test_plain_admin_cannot_manage_permissions(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'is_protected' => false]);
        $guru = User::factory()->create(['role' => User::ROLE_GURU]);

        $this->actingAs($admin)
            ->putJson("/api/users/{$guru->id}/permissions", ['allow' => ['students.create']])
            ->assertStatus(403);
    }

    public function test_admin_utama_can_grant_extra_permission(): void
    {
        $admin = $this->protectedAdmin();
        $bendahara = User::factory()->create([
            'role' => User::ROLE_BENDAHARA_LPI,
            'unit' => 'lpi',
        ]);

        $this->actingAs($bendahara)->getJson('/api/students/template')->assertStatus(403);

        $this->actingAs($admin)
            ->putJson("/api/users/{$bendahara->id}/permissions", ['allow' => ['students.import']])
            ->assertOk()
            ->assertJsonPath('can_manage', true)
            ->assertJsonFragment(['students.import']);

        $this->assertDatabaseHas('user_permissions', [
            'user_id' => $bendahara->id,
            'permission' => 'students.import',
            'effect' => UserPermission::EFFECT_ALLOW,
            'granted_by' => $admin->id,
        ]);

        $this->actingAs($bendahara->fresh())->getJson('/api/students/template')->assertOk();
    }

    public function test_admin_utama_can_revoke_role_permission(): void
    {
        $admin = $this->protectedAdmin();
        $guru = User::factory()->create(['role' => User::ROLE_GURU]);

        $this->actingAs($guru)->getJson('/api/classrooms')->assertOk();

        $this->actingAs($admin)
            ->putJson("/api/users/{$guru->id}/permissions", ['deny' => ['classrooms.view']])
            ->assertOk();

        $this->assertDatabaseHas('user_permissions', [
            'user_id' => $guru->id,
            'permission' => 'classrooms.view',
            'effect' => UserPermission::EFFECT_DENY,
        ]);

        $this->actingAs($guru->fresh())->getJson('/api/classrooms')->assertStatus(403);
        $this->actingAs($guru->fresh())->getJson('/api/students')->assertOk();
    }

    public function test_role_is_not_changed_when_permissions_change(): void
    {
        $admin = $this->protectedAdmin();
        $guru = User::factory()->create(['role' => User::ROLE_GURU]);

        $this->actingAs($admin)
            ->putJson("/api/users/{$guru->id}/permissions", ['allow' => ['teachers.view']])
            ->assertOk();

        $this->assertSame(User::ROLE_GURU, $guru->fresh()->role);
    }

    public function test_user_cannot_edit_own_permissions(): void
    {
        $admin = $this->protectedAdmin();

        $this->actingAs($admin)
            ->putJson("/api/users/{$admin->id}/permissions", ['allow' => ['users.create']])
            ->assertStatus(403);
    }

    public function test_protected_admin_permissions_cannot_be_edited(): void
    {
        $admin = $this->protectedAdmin();
        $other = User::factory()->create(['role' => User::ROLE_ADMIN, 'is_protected' => false]);
        $other->permissionOverrides()->create([
            'permission' => 'permissions.manage',
            'effect' => UserPermission::EFFECT_ALLOW,
            'granted_by' => $admin->id,
        ]);

        $this->actingAs($other)
            ->putJson("/api/users/{$admin->id}/permissions", ['deny' => ['users.view']])
            ->assertStatus(403);
    }

    public function test_privileged_permission_cannot_be_granted_to_non_admin(): void
    {
        $admin = $this->protectedAdmin();
        $guru = User::factory()->create(['role' => User::ROLE_GURU]);

        $this->actingAs($admin)
            ->putJson("/api/users/{$guru->id}/permissions", ['allow' => ['users.create']])
            ->assertStatus(422);
    }

    public function test_finance_permission_requires_unit_on_non_finance_account(): void
    {
        $admin = $this->protectedAdmin();
        $guru = User::factory()->create(['role' => User::ROLE_GURU, 'unit' => null]);

        $this->actingAs($admin)
            ->putJson("/api/users/{$guru->id}/permissions", ['allow' => ['finance.view']])
            ->assertStatus(422);
    }

    public function test_overlapping_allow_and_deny_is_rejected(): void
    {
        $admin = $this->protectedAdmin();
        $guru = User::factory()->create(['role' => User::ROLE_GURU]);

        $this->actingAs($admin)
            ->putJson("/api/users/{$guru->id}/permissions", [
                'allow' => ['students.create'],
                'deny' => ['students.create'],
            ])
            ->assertStatus(422);
    }

    public function test_unknown_permission_is_rejected(): void
    {
        $admin = $this->protectedAdmin();
        $guru = User::factory()->create(['role' => User::ROLE_GURU]);

        $this->actingAs($admin)
            ->putJson("/api/users/{$guru->id}/permissions", ['allow' => ['students.hack']])
            ->assertStatus(422);
    }

    public function test_unit_scope_is_preserved_for_granted_finance_access(): void
    {
        $admin = $this->protectedAdmin();
        $guru = User::factory()->create(['role' => User::ROLE_GURU, 'unit' => 'lpi']);

        FinanceTransaction::factory()->unit('lpi')->income()->create(['description' => 'LPI only']);
        FinanceTransaction::factory()->unit('sma')->income()->create(['description' => 'SMA only']);

        $this->actingAs($admin)
            ->putJson("/api/users/{$guru->id}/permissions", [
                'allow' => ['finance.view', 'finance.transactions.view'],
            ])
            ->assertOk();

        $this->actingAs($guru->fresh())
            ->getJson('/api/finance/transactions?type=income')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.description', 'LPI only');
    }

    public function test_guru_cannot_manage_permissions(): void
    {
        $guru = User::factory()->create(['role' => User::ROLE_GURU]);
        $target = User::factory()->create(['role' => User::ROLE_GURU]);

        $this->actingAs($guru)
            ->putJson("/api/users/{$target->id}/permissions", ['allow' => ['students.create']])
            ->assertStatus(403);
    }

    public function test_login_and_me_include_effective_permissions(): void
    {
        $guru = User::factory()->create([
            'role' => User::ROLE_GURU,
            'email' => 'guru-perm@madani.test',
            'password' => 'password123',
        ]);

        $this->postJson('/api/login', [
            'identity' => $guru->email,
            'password' => 'password123',
        ])
            ->assertOk()
            ->assertJsonStructure(['token', 'user' => ['permissions', 'role_permissions', 'permission_overrides']]);

        $this->actingAs($guru)
            ->getJson('/api/me')
            ->assertOk()
            ->assertJsonFragment(['role' => User::ROLE_GURU])
            ->assertJsonStructure(['permissions', 'role_permissions', 'permission_overrides']);
    }

    public function test_permission_catalog_is_available_to_admin(): void
    {
        $admin = $this->protectedAdmin();

        $this->actingAs($admin)
            ->getJson('/api/permissions/catalog')
            ->assertOk()
            ->assertJsonStructure(['modules', 'privileged', 'roles']);
    }

    public function test_granting_permission_does_not_require_classroom_record(): void
    {
        $admin = $this->protectedAdmin();
        $guru = User::factory()->create(['role' => User::ROLE_GURU]);
        Classroom::create(['name' => 'X-1', 'grade' => 10, 'academic_year' => '2026/2027', 'unit' => 'sma']);

        $this->actingAs($admin)
            ->putJson("/api/users/{$guru->id}/permissions", ['allow' => ['teachers.view']])
            ->assertOk();

        $this->actingAs($guru->fresh())->getJson('/api/teachers')->assertOk();
    }
}
