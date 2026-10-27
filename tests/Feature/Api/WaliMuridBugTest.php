<?php

namespace Tests\Feature\Api;

use App\Models\Classroom;
use App\Models\Score;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class WaliMuridBugTest extends TestCase
{
    use RefreshDatabase;

    private function classroom(): Classroom
    {
        return Classroom::create(['name' => 'X-1', 'grade' => 10, 'academic_year' => '2026/2027']);
    }

    private function wali(string $email = 'wali@madani.test'): User
    {
        return User::factory()->create(['email' => $email, 'role' => 'wali_murid', 'is_active' => true, 'password' => Hash::make('password123')]);
    }

    public function test_1_wali_a_login_nisn_returns_correct_student(): void
    {
        $cl = $this->classroom();
        $waliA = $this->wali('waliA@madani.test');
        $sA = Student::create(['nis' => '2026001', 'nisn' => '0071234567', 'name' => 'Santri A', 'gender' => 'L', 'classroom_id' => $cl->id, 'parent_id' => $waliA->id, 'status' => 'aktif']);

        $res = $this->postJson('/api/login', ['identity' => '0071234567', 'password' => 'password123']);
        $res->assertOk()->assertJsonPath('user.id', $waliA->id);

        $token = $res->json('token');
        $list = $this->withHeader('Authorization', "Bearer $token")->getJson('/api/students')->assertOk();
        $names = collect($list->json('data'))->pluck('name');
        $this->assertTrue($names->contains('Santri A'));
        $this->assertFalse($names->contains('Ahmad Fauzi'));
    }

    public function test_2_wali_b_login_nisn_returns_correct_student(): void
    {
        $cl = $this->classroom();
        $waliB = $this->wali('waliB@madani.test');
        Student::create(['nis' => '2026002', 'nisn' => '0071234568', 'name' => 'Santri B', 'gender' => 'L', 'classroom_id' => $cl->id, 'parent_id' => $waliB->id, 'status' => 'aktif']);

        $res = $this->postJson('/api/login', ['identity' => '0071234568', 'password' => 'password123']);
        $res->assertOk()->assertJsonPath('user.id', $waliB->id);
        $token = $res->json('token');
        $list = $this->withHeader('Authorization', "Bearer $token")->getJson('/api/students')->assertOk();
        $this->assertEquals('Santri B', $list->json('data.0.name'));
    }

    public function test_3_student_without_parent_login_401(): void
    {
        $cl = $this->classroom();
        Student::create(['nis' => '2026003', 'nisn' => '0079990001', 'name' => 'Orphan', 'gender' => 'L', 'classroom_id' => $cl->id, 'parent_id' => null, 'status' => 'aktif']);
        $this->postJson('/api/login', ['identity' => '0079990001', 'password' => 'password123'])->assertStatus(401);
    }

    public function test_4_wali_with_two_children_dashboard_returns_two(): void
    {
        $cl = $this->classroom();
        $wali = $this->wali('waliMulti@madani.test');
        Student::create(['nis' => '2026010', 'nisn' => '0070000010', 'name' => 'Anak 1', 'gender' => 'L', 'classroom_id' => $cl->id, 'parent_id' => $wali->id, 'status' => 'aktif']);
        Student::create(['nis' => '2026011', 'nisn' => '0070000011', 'name' => 'Anak 2', 'gender' => 'L', 'classroom_id' => $cl->id, 'parent_id' => $wali->id, 'status' => 'aktif']);

        $res = $this->actingAs($wali)->getJson('/api/students')->assertOk();
        $this->assertCount(2, $res->json('data'));
    }

    public function test_5_wali_a_cannot_view_student_b(): void
    {
        $cl = $this->classroom();
        $waliA = $this->wali('waliA2@madani.test');
        $waliB = $this->wali('waliB2@madani.test');
        $sB = Student::create(['nis' => '2026020', 'nisn' => '0070000020', 'name' => 'Milik B', 'gender' => 'L', 'classroom_id' => $cl->id, 'parent_id' => $waliB->id, 'status' => 'aktif']);

        $this->actingAs($waliA)->getJson("/api/students/{$sB->id}")->assertStatus(403);

        $list = $this->actingAs($waliA)->getJson('/api/students')->assertOk();
        $ids = collect($list->json('data'))->pluck('id');
        $this->assertFalse($ids->contains($sB->id));
    }

    public function test_6_parent_id_admin_rejected(): void
    {
        $admin = User::factory()->create(['role' => 'admin_utama']);
        $cl = $this->classroom();
        $this->actingAs($admin)->postJson('/api/students', [
            'nis' => 'N100', 'nisn' => 'NISN100', 'name' => 'Test', 'classroom_id' => $cl->id, 'parent_id' => $admin->id,
        ])->assertStatus(422);
    }

    public function test_7_parent_id_guru_rejected(): void
    {
        $admin = User::factory()->create(['role' => 'admin_utama']);
        $guru = User::factory()->create(['role' => 'guru']);
        $cl = $this->classroom();
        $this->actingAs($admin)->postJson('/api/students', [
            'nis' => 'N101', 'nisn' => 'NISN101', 'name' => 'Test', 'classroom_id' => $cl->id, 'parent_id' => $guru->id,
        ])->assertStatus(422);
    }

    public function test_8_parent_id_bendahara_rejected(): void
    {
        $admin = User::factory()->create(['role' => 'admin_utama']);
        $bend = User::factory()->create(['role' => 'bendahara']);
        $cl = $this->classroom();
        $this->actingAs($admin)->postJson('/api/students', [
            'nis' => 'N102', 'nisn' => 'NISN102', 'name' => 'Test', 'classroom_id' => $cl->id, 'parent_id' => $bend->id,
        ])->assertStatus(422);
    }

    public function test_9_duplicate_nisn_rejected(): void
    {
        $admin = User::factory()->create(['role' => 'admin_utama']);
        $cl = $this->classroom();
        $wali = $this->wali();
        Student::create(['nis' => 'N200', 'nisn' => '0071234999', 'name' => 'Existing', 'gender' => 'L', 'classroom_id' => $cl->id, 'parent_id' => $wali->id, 'status' => 'aktif']);
        $this->actingAs($admin)->postJson('/api/students', [
            'nis' => 'N201', 'nisn' => '0071234999', 'name' => 'Dup', 'classroom_id' => $cl->id,
        ])->assertStatus(422);
    }

    public function test_10_leading_zero_nisn_login(): void
    {
        $cl = $this->classroom();
        $wali = $this->wali('waliZero@madani.test');
        Student::create(['nis' => '2026030', 'nisn' => '0071234567', 'name' => 'Zero', 'gender' => 'L', 'classroom_id' => $cl->id, 'parent_id' => $wali->id, 'status' => 'aktif']);

        $this->postJson('/api/login', ['identity' => '0071234567', 'password' => 'password123'])->assertOk();
        $this->postJson('/api/login', ['identity' => ' 0071234567 ', 'password' => 'password123'])->assertOk();
        $this->postJson('/api/login', ['identity' => '71234567', 'password' => 'password123'])->assertStatus(401);
    }

    public function test_11_dashboard_not_hardcoded_ahmad_fauzi(): void
    {
        $content = file_get_contents(resource_path('js/Pages/WaliMurid/Dashboard.vue'));
        $this->assertStringNotContainsString('Ahmad Fauzi', $content, 'Dashboard masih hardcoded Ahmad Fauzi');
        $this->assertStringNotContainsString('2026001', $content, 'Dashboard masih hardcoded NIS 2026001');
        $this->assertStringContainsString('/api/students', $content, 'Dashboard harus fetch /api/students');
        $this->assertStringContainsString('Belum ada data anak', $content, 'Dashboard harus punya empty state');
    }

    public function test_parent_id_nullable_allowed(): void
    {
        $admin = User::factory()->create(['role' => 'admin_utama']);
        $cl = $this->classroom();
        $this->actingAs($admin)->postJson('/api/students', [
            'nis' => 'N300', 'nisn' => 'NISN300', 'name' => 'No Wali', 'classroom_id' => $cl->id, 'parent_id' => null,
        ])->assertStatus(201);
        $this->assertDatabaseHas('students', ['nis' => 'N300', 'parent_id' => null]);
    }

    public function test_parent_id_can_be_cleared_via_update(): void
    {
        $admin = User::factory()->create(['role' => 'admin_utama']);
        $cl = $this->classroom();
        $wali = $this->wali('waliClear@madani.test');
        $s = Student::create(['nis' => 'N400', 'nisn' => 'NISN400', 'name' => 'ToClear', 'gender' => 'L', 'classroom_id' => $cl->id, 'parent_id' => $wali->id, 'status' => 'aktif']);
        $this->actingAs($admin)->putJson("/api/students/{$s->id}", ['parent_id' => null])->assertOk();
        $this->assertDatabaseHas('students', ['id' => $s->id, 'parent_id' => null]);
        $this->assertDatabaseHas('users', ['id' => $wali->id]);
    }

    public function test_wali_can_see_own_scores_only(): void
    {
        $cl = $this->classroom();
        $waliA = $this->wali('waliScoreA@madani.test');
        $waliB = $this->wali('waliScoreB@madani.test');
        $sA = Student::create(['nis' => 'N500', 'nisn' => '0075000001', 'name' => 'Score A', 'gender' => 'L', 'classroom_id' => $cl->id, 'parent_id' => $waliA->id, 'status' => 'aktif']);
        $sB = Student::create(['nis' => 'N501', 'nisn' => '0075000002', 'name' => 'Score B', 'gender' => 'L', 'classroom_id' => $cl->id, 'parent_id' => $waliB->id, 'status' => 'aktif']);
        $subj = Subject::create(['name' => 'Math', 'code' => 'MTK']);
        Score::create(['student_id' => $sA->id, 'subject_id' => $subj->id, 'classroom_id' => $cl->id, 'teacher_id' => $waliA->id, 'value' => 90, 'semester' => 'ganjil']);
        Score::create(['student_id' => $sB->id, 'subject_id' => $subj->id, 'classroom_id' => $cl->id, 'teacher_id' => $waliA->id, 'value' => 80, 'semester' => 'ganjil']);

        $res = $this->actingAs($waliA)->getJson('/api/wali/scores')->assertOk();
        $json = $res->json();
        $allStudentIds = collect($json)->keys()->map(fn ($k) => (int) $k);
        $this->assertTrue($allStudentIds->contains($sA->id));
        $this->assertFalse($allStudentIds->contains($sB->id));
    }
}
