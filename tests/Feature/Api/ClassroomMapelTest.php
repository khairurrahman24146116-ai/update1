<?php

namespace Tests\Feature\Api;

use App\Models\Classroom;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClassroomMapelTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_classroom(): void
    {
        $admin = User::factory()->create(['role' => 'admin_utama']);
        $this->actingAs($admin)->postJson('/api/classrooms', ['name' => 'X-1', 'grade' => 10, 'academic_year' => '2026/2027'])->assertStatus(201);
        $this->assertDatabaseHas('classrooms', ['name' => 'X-1', 'academic_year' => '2026/2027']);
    }

    public function test_admin_can_update_classroom(): void
    {
        $admin = User::factory()->create(['role' => 'admin_utama']);
        $c = Classroom::create(['name' => 'X-1', 'grade' => 10, 'academic_year' => '2026/2027']);
        $this->actingAs($admin)->putJson("/api/classrooms/{$c->id}", ['name' => 'X-2'])->assertOk()->assertJsonPath('name', 'X-2');
    }

    public function test_duplicate_same_year_rejected(): void
    {
        $admin = User::factory()->create(['role' => 'admin_utama']);
        Classroom::create(['name' => 'X-1', 'grade' => 10, 'academic_year' => '2026/2027']);
        $this->actingAs($admin)->postJson('/api/classrooms', ['name' => 'X-1', 'grade' => 10, 'academic_year' => '2026/2027'])->assertStatus(422);
    }

    public function test_same_name_different_year_allowed(): void
    {
        $admin = User::factory()->create(['role' => 'admin_utama']);
        Classroom::create(['name' => 'X-1', 'grade' => 10, 'academic_year' => '2026/2027']);
        $this->actingAs($admin)->postJson('/api/classrooms', ['name' => 'X-1', 'grade' => 10, 'academic_year' => '2027/2028'])->assertStatus(201);
    }

    public function test_guru_cannot_create_classroom(): void
    {
        $guru = User::factory()->create(['role' => 'guru']);
        $this->actingAs($guru)->postJson('/api/classrooms', ['name' => 'X-9', 'grade' => 10])->assertStatus(403);
    }

    public function test_wali_cannot_create_classroom(): void
    {
        $wali = User::factory()->create(['role' => 'wali_murid']);
        $this->actingAs($wali)->postJson('/api/classrooms', ['name' => 'X-9', 'grade' => 10])->assertStatus(403);
    }

    public function test_admin_create_subject(): void
    {
        $admin = User::factory()->create(['role' => 'admin_utama']);
        $this->actingAs($admin)->postJson('/api/subjects', ['name' => 'Matematika', 'code' => 'MTK'])->assertStatus(201);
    }

    public function test_duplicate_subject_code_rejected(): void
    {
        $admin = User::factory()->create(['role' => 'admin_utama']);
        $this->actingAs($admin)->postJson('/api/subjects', ['name' => 'Matematika', 'code' => 'MTK'])->assertStatus(201);
        $this->actingAs($admin)->postJson('/api/subjects', ['name' => 'Matematika 2', 'code' => 'MTK'])->assertStatus(422);
    }

    public function test_guru_cannot_create_subject(): void
    {
        $guru = User::factory()->create(['role' => 'guru']);
        $this->actingAs($guru)->postJson('/api/subjects', ['name' => 'Fisika', 'code' => 'FIS'])->assertStatus(403);
    }

    public function test_admin_edit_subject(): void
    {
        $admin = User::factory()->create(['role' => 'admin_utama']);
        $r = $this->actingAs($admin)->postJson('/api/subjects', ['name' => 'Kimia', 'code' => 'KIM'])->assertStatus(201);
        $id = $r->json('id');
        $this->actingAs($admin)->putJson("/api/subjects/{$id}", ['name' => 'Kimia Updated'])->assertOk();
    }
}
