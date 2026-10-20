<?php

namespace Tests\Feature\Api;

use App\Models\ActivityLog;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthLoginTest extends TestCase
{
    use RefreshDatabase;

    private function classroom(): Classroom
    {
        return Classroom::create(['name' => 'X-1', 'grade' => 10, 'academic_year' => '2026/2027']);
    }

    public function test_unregistered_email_reports_account_not_found(): void
    {
        $this->postJson('/api/login', [
            'identity' => 'tidak-ada@madani.test',
            'password' => 'password123',
        ])
            ->assertStatus(401)
            ->assertJsonPath('code', 'account_not_found')
            ->assertJsonPath('message', 'Akun belum terdaftar. Periksa kembali email Anda.');
    }

    public function test_registered_email_with_wrong_password_reports_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'guru@madani.test',
            'role' => 'guru',
            'is_active' => true,
            'password' => 'password123',
        ]);

        $this->postJson('/api/login', [
            'identity' => 'guru@madani.test',
            'password' => 'salah-sekali',
        ])
            ->assertStatus(401)
            ->assertJsonPath('code', 'invalid_credentials')
            ->assertJsonPath('message', 'Username dan password salah.');
    }

    public function test_registered_email_with_correct_password_logs_in(): void
    {
        $user = User::factory()->create([
            'email' => 'guru2@madani.test',
            'role' => 'guru',
            'is_active' => true,
            'password' => 'password123',
        ]);

        $this->postJson('/api/login', [
            'identity' => 'guru2@madani.test',
            'password' => 'password123',
        ])
            ->assertOk()
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonStructure(['token', 'user']);
    }

    public function test_inactive_account_reports_account_inactive(): void
    {
        User::factory()->create([
            'email' => 'nonaktif@madani.test',
            'role' => 'guru',
            'is_active' => false,
            'password' => 'password123',
        ]);

        $this->postJson('/api/login', [
            'identity' => 'nonaktif@madani.test',
            'password' => 'password123',
        ])
            ->assertStatus(403)
            ->assertJsonPath('code', 'account_inactive');
    }

    public function test_unknown_nisn_reports_account_not_found(): void
    {
        $this->postJson('/api/login', [
            'identity' => '0079990009',
            'password' => 'password123',
        ])
            ->assertStatus(401)
            ->assertJsonPath('code', 'account_not_found')
            ->assertJsonPath('message', 'Akun belum terdaftar. Periksa kembali NISN Anda.');
    }

    public function test_known_nisn_with_wrong_password_reports_invalid_credentials(): void
    {
        $wali = User::factory()->create([
            'email' => 'wali-auth@madani.test',
            'role' => 'wali_murid',
            'is_active' => true,
            'password' => 'password123',
        ]);
        Student::create([
            'nis' => '2026999',
            'nisn' => '0071234599',
            'name' => 'Santri Auth',
            'gender' => 'L',
            'classroom_id' => $this->classroom()->id,
            'parent_id' => $wali->id,
            'status' => 'aktif',
        ]);

        $this->postJson('/api/login', [
            'identity' => '0071234599',
            'password' => 'salah-sekali',
        ])
            ->assertStatus(401)
            ->assertJsonPath('code', 'invalid_credentials');
    }

    public function test_student_without_parent_reports_account_not_found(): void
    {
        Student::create([
            'nis' => '2026888',
            'nisn' => '0079998888',
            'name' => 'Santri Yatim Akun',
            'gender' => 'L',
            'classroom_id' => $this->classroom()->id,
            'parent_id' => null,
            'status' => 'aktif',
        ]);

        $this->postJson('/api/login', [
            'identity' => '0079998888',
            'password' => 'password123',
        ])
            ->assertStatus(401)
            ->assertJsonPath('code', 'account_not_found');
    }

    public function test_successful_login_is_logged_without_password(): void
    {
        $user = User::factory()->create([
            'email' => 'log-login@madani.test',
            'role' => 'guru',
            'is_active' => true,
            'password' => 'password123',
        ]);

        $this->postJson('/api/login', [
            'identity' => 'log-login@madani.test',
            'password' => 'password123',
        ])->assertOk();

        $log = ActivityLog::query()->where('action', 'login')->sole();
        $this->assertSame($user->id, $log->user_id);
        $this->assertSame('email', $log->metadata['channel']);
        $this->assertArrayNotHasKey('password', $log->metadata);
    }

    public function test_unknown_login_attempt_is_logged_as_guest(): void
    {
        $this->postJson('/api/login', [
            'identity' => 'tidak-dikenal@madani.test',
            'password' => 'password123',
        ])->assertStatus(401);

        $log = ActivityLog::query()->where('action', 'login.failed')->sole();
        $this->assertNull($log->user_id);
        $this->assertSame('Tamu', $log->user_name);
        $this->assertSame('account_not_found', $log->metadata['reason']);
        $this->assertArrayNotHasKey('password', $log->metadata);
    }

    public function test_logout_is_logged(): void
    {
        $user = User::factory()->create([
            'email' => 'log-logout@madani.test',
            'role' => 'guru',
            'is_active' => true,
            'password' => 'password123',
        ]);
        $token = $this->postJson('/api/login', [
            'identity' => 'log-logout@madani.test',
            'password' => 'password123',
        ])->json('token');

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/logout')
            ->assertNoContent();

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'logout',
            'user_id' => $user->id,
        ]);
    }
}
