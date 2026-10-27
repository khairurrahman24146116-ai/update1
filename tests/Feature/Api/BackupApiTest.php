<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BackupApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_backup(): void
    {
        Storage::fake('backups');
        $admin = User::factory()->create(['role' => 'admin_utama']);
        $res = $this->actingAs($admin)->postJson('/api/backups')->assertOk()->assertJsonStructure(['filename', 'size']);
        $fn = $res->json('filename');
        Storage::disk('backups')->assertExists($fn);
        $this->assertDatabaseHas('activity_logs', ['action' => 'backup.create']);
    }

    public function test_backup_file_private(): void
    {
        Storage::fake('backups');
        $admin = User::factory()->create(['role' => 'admin_utama']);
        $res = $this->actingAs($admin)->postJson('/api/backups')->assertOk();
        $fn = $res->json('filename');
        $this->assertTrue((bool) preg_match('/^madani-\d{8}-\d{6}\.sql\.gz$/', $fn));
        $this->assertStringNotContainsString('..', $fn);
        $this->assertStringNotContainsString('/', $fn);
    }

    public function test_guru_cannot_backup(): void
    {
        $guru = User::factory()->create(['role' => 'guru']);
        $this->actingAs($guru)->postJson('/api/backups')->assertStatus(403);
    }

    public function test_wali_cannot_backup(): void
    {
        $wali = User::factory()->create(['role' => 'wali_murid']);
        $this->actingAs($wali)->postJson('/api/backups')->assertStatus(403);
        $this->actingAs($wali)->getJson('/api/backups/download/madani-20260101-120000.sql.gz')->assertStatus(403);
    }

    public function test_bendahara_cannot_backup(): void
    {
        $b = User::factory()->create(['role' => 'bendahara']);
        $this->actingAs($b)->postJson('/api/backups')->assertStatus(403);
    }

    public function test_path_traversal_rejected(): void
    {
        Storage::fake('backups');
        $admin = User::factory()->create(['role' => 'admin_utama']);
        $this->actingAs($admin)->getJson('/api/backups/download/madani-evil.sql.gz')->assertStatus(404);
        $this->actingAs($admin)->getJson('/api/backups/download/madani-20260101-120000.sql')->assertStatus(404);
        $this->actingAs($admin)->getJson('/api/backups/download/notmadani-20260101-120000.sql.gz')->assertStatus(404);
    }

    public function test_backup_download_admin_only(): void
    {
        Storage::fake('backups');
        $admin = User::factory()->create(['role' => 'admin_utama']);
        $res = $this->actingAs($admin)->postJson('/api/backups')->assertOk();
        $fn = $res->json('filename');
        $this->actingAs($admin)->get('/api/backups/download/'.$fn)->assertOk();
        $guru = User::factory()->create(['role' => 'guru']);
        $this->actingAs($guru)->get('/api/backups/download/'.$fn)->assertStatus(403);
    }
}
