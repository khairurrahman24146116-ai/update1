<?php

namespace Tests\Feature\Api;

use App\Models\ActivityLog;
use App\Models\Classroom;
use App\Models\Subject;
use App\Models\Unit;
use App\Models\User;
use App\Models\UserPermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityLogApiTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_ADMIN_UTAMA,
            'is_protected' => false,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function log(array $overrides = []): ActivityLog
    {
        return ActivityLog::create(array_merge([
            'user_name' => 'Admin Uji',
            'user_role' => User::ROLE_ADMIN_UTAMA,
            'action' => 'login',
            'module' => 'auth',
            'description' => 'Login berhasil',
            'metadata' => ['channel' => 'email'],
            'ip_address' => '127.0.0.1',
            'created_at' => now(),
        ], $overrides));
    }

    public function test_admin_utama_can_list_and_filter_logs(): void
    {
        $this->log();
        $this->log([
            'action' => 'finance.transaction.create',
            'module' => 'finance',
            'description' => 'Menambah transaksi keuangan',
        ]);

        $response = $this->actingAs($this->admin())
            ->getJson('/api/activity-logs?module=finance&per_page=10')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.action', 'finance.transaction.create')
            ->assertJsonPath('data.0.module_label', 'Keuangan');

        $this->assertSame(1, $response->json('total'));
    }

    public function test_logs_can_be_filtered_by_role_and_unit(): void
    {
        $lpi = (int) Unit::where('kode', 'LPI')->value('id');
        $sma = (int) Unit::where('kode', 'SMA')->value('id');

        $this->log(['user_role' => 'operator', 'unit_id' => $lpi, 'user_name' => 'Operator LPI']);
        $this->log(['user_role' => 'operator', 'unit_id' => $sma, 'user_name' => 'Operator SMA']);
        $this->log(['user_role' => 'guru', 'unit_id' => $lpi, 'user_name' => 'Guru LPI']);

        $admin = $this->admin();

        $this->actingAs($admin)
            ->getJson('/api/activity-logs?role=operator')
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->actingAs($admin)
            ->getJson('/api/activity-logs?unit=sma')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.user_name', 'Operator SMA');

        $this->actingAs($admin)
            ->getJson('/api/activity-logs?only_operators=1&unit=lpi')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.user_name', 'Operator LPI');
    }

    public function test_list_sanitizes_legacy_metadata(): void
    {
        $this->log([
            'metadata' => [
                'password' => 'rahasia',
                'token' => 'abc',
                'phone' => '081234567890',
                'note' => 'aman',
            ],
        ]);

        $metadata = $this->actingAs($this->admin())
            ->getJson('/api/activity-logs')
            ->assertOk()
            ->json('data.0.metadata');

        $this->assertArrayNotHasKey('password', $metadata);
        $this->assertArrayNotHasKey('token', $metadata);
        $this->assertSame('0812****890', $metadata['phone']);
        $this->assertSame('aman', $metadata['note']);
    }

    public function test_non_admin_utama_cannot_view_logs(): void
    {
        foreach ([User::ROLE_GURU, User::ROLE_ADMIN, User::ROLE_OPERATOR, User::ROLE_KEPALA_SEKOLAH] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)->getJson('/api/activity-logs')->assertForbidden();
            $this->actingAs($user)->getJson('/api/activity-logs/options')->assertForbidden();
        }
    }

    public function test_admin_utama_without_logs_permission_is_denied(): void
    {
        $admin = $this->admin();
        UserPermission::create([
            'user_id' => $admin->id,
            'permission' => 'logs.view',
            'effect' => UserPermission::EFFECT_DENY,
        ]);

        $this->actingAs($admin)->getJson('/api/activity-logs')->assertForbidden();
    }

    public function test_show_returns_log_detail(): void
    {
        $log = $this->log(['target_type' => User::class, 'target_id' => 7]);

        $this->actingAs($this->admin())
            ->getJson("/api/activity-logs/{$log->id}")
            ->assertOk()
            ->assertJsonPath('id', $log->id)
            ->assertJsonPath('target_label', 'User#7');
    }

    public function test_csv_export_returns_file_and_writes_audit_log(): void
    {
        $this->log();

        $response = $this->actingAs($this->admin())
            ->get('/api/activity-logs/export')
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $this->assertStringContainsString('Waktu,Pengguna,Role,Modul,Aksi', $response->streamedContent());
        $this->assertDatabaseHas('activity_logs', ['action' => 'activity_log.export']);
    }

    public function test_pdf_export_returns_pdf_and_writes_audit_log(): void
    {
        $this->log();

        $this->actingAs($this->admin())
            ->get('/api/activity-logs/pdf')
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->assertDatabaseHas('activity_logs', ['action' => 'activity_log.pdf']);
    }

    public function test_pdf_rejects_more_than_configured_row_limit(): void
    {
        config(['activity-log.pdf_max_rows' => 1]);
        $this->log();
        $this->log(['action' => 'logout', 'module' => 'auth']);

        $this->actingAs($this->admin())
            ->getJson('/api/activity-logs/pdf')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('report');

        $this->assertDatabaseMissing('activity_logs', ['action' => 'activity_log.pdf']);
    }

    public function test_activity_logs_are_append_only(): void
    {
        $log = $this->log();
        $admin = $this->admin();

        $this->actingAs($admin)
            ->postJson('/api/activity-logs/prune', ['confirm' => true, 'months' => 1])
            ->assertStatus(405);

        $this->actingAs($admin)
            ->putJson('/api/activity-logs/retention', ['months' => 1])
            ->assertStatus(405);

        $this->assertDatabaseHas('activity_logs', ['id' => $log->id]);
    }

    public function test_schedule_write_is_logged(): void
    {
        $admin = $this->admin();
        $guru = User::factory()->create(['role' => User::ROLE_GURU]);
        $classroom = Classroom::create([
            'name' => 'X-Audit',
            'grade' => 10,
            'academic_year' => '2026/2027',
            'unit' => 'sma',
            'jenjang' => Classroom::JENJANG_SMA,
        ]);
        $subject = Subject::create(['name' => 'Audit', 'code' => 'AUD']);

        $schedule = $this->actingAs($admin)
            ->postJson('/api/schedules', [
                'classroom_id' => $classroom->id,
                'subject_id' => $subject->id,
                'teacher_id' => $guru->id,
                'day_of_week' => 'mon',
                'start_time' => '08:00',
                'end_time' => '09:00',
                'academic_year' => '2026/2027',
            ])
            ->assertCreated()
            ->json();

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'schedule.create',
            'user_id' => $admin->id,
            'target_id' => $schedule['id'],
        ]);
    }
}
