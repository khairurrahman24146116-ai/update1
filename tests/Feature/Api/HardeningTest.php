<?php

namespace Tests\Feature\Api;

use App\Models\Classroom;
use App\Models\SiteSetting;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_cannot_list_users(): void
    {
        foreach (['guru', 'wali_murid', 'bendahara'] as $role) {
            $u = User::factory()->create(['role' => $role]);
            $this->actingAs($u)->getJson('/api/users')->assertStatus(403);
        }
    }

    public function test_admin_can_list_users_without_password_hash(): void
    {
        $admin = User::factory()->create(['role' => 'admin_utama']);
        User::factory()->create(['role' => 'guru']);
        $res = $this->actingAs($admin)->getJson('/api/users')->assertOk();
        $first = $res->json()[0] ?? $res->json('data')[0] ?? null;
        $this->assertNotNull($first);
        $this->assertArrayNotHasKey('password', $first);
    }

    public function test_admin_can_read_user_stats(): void
    {
        $admin = User::factory()->create(['role' => 'admin_utama']);
        User::factory()->count(3)->create(['role' => 'guru']);
        User::factory()->create(['role' => 'wali_murid']);

        $this->actingAs($admin)->getJson('/api/users/stats')
            ->assertOk()
            ->assertJsonPath('total', 5)
            ->assertJsonPath('guru', 3);
    }

    public function test_non_admin_cannot_read_user_stats(): void
    {
        $guru = User::factory()->create(['role' => 'guru']);
        $this->actingAs($guru)->getJson('/api/users/stats')->assertStatus(403);
    }

    public function test_users_index_is_paginated(): void
    {
        $admin = User::factory()->create(['role' => 'admin_utama']);
        User::factory()->count(4)->create(['role' => 'guru']);

        $res = $this->actingAs($admin)->getJson('/api/users?per_page=2')->assertOk();
        $this->assertCount(2, $res->json('data'));
        $this->assertEquals(5, $res->json('total'));
        $this->assertEquals(3, $res->json('last_page'));
    }

    public function test_guru_cannot_create_account(): void
    {
        $guru = User::factory()->create(['role' => 'guru']);
        $this->actingAs($guru)->postJson('/api/accounts', [
            'name' => 'X',
            'email' => 'x@x.com',
            'password' => 'secret1',
            'role' => 'guru',
            'units' => [['code' => 'SMA', 'jabatan' => 'Guru']],
        ])->assertStatus(403);
    }

    public function test_site_upload_rejects_php(): void
    {
        $admin = User::factory()->create(['role' => 'admin_utama']);
        $file = UploadedFile::fake()->create('evil.php', 10, 'application/x-php');
        $this->actingAs($admin)->postJson('/api/site-settings/upload', ['file' => $file, 'field' => 'logo_path'])->assertStatus(422);
    }

    public function test_site_upload_accepts_jpg(): void
    {
        $admin = User::factory()->create(['role' => 'admin_utama']);
        $file = UploadedFile::fake()->image('logo.jpg', 100, 100);
        $this->actingAs($admin)->post('/api/site-settings/upload', ['file' => $file, 'field' => 'logo_path'])->assertOk();
    }

    public function test_site_upload_rejects_svg(): void
    {
        $admin = User::factory()->create(['role' => 'admin_utama']);
        $file = UploadedFile::fake()->create('icon.svg', 10, 'image/svg+xml');
        $this->actingAs($admin)->postJson('/api/site-settings/upload', ['file' => $file, 'field' => 'logo_path'])->assertStatus(422);
    }

    public function test_non_admin_cannot_manage_homepage_settings(): void
    {
        foreach (['guru', 'wali_murid', 'bendahara'] as $role) {
            $u = User::factory()->create(['role' => $role]);
            $this->actingAs($u)->getJson('/api/homepage-settings')->assertStatus(403);
            $this->actingAs($u)->putJson('/api/homepage-settings', [])->assertStatus(403);
        }
    }

    public function test_admin_can_read_homepage_settings(): void
    {
        $admin = User::factory()->create(['role' => 'admin_utama']);
        $this->actingAs($admin)->getJson('/api/homepage-settings')
            ->assertOk()
            ->assertJsonStructure(['settings', 'hero_media']);
    }

    public function test_homepage_media_upload_rejects_svg(): void
    {
        $admin = User::factory()->create(['role' => 'admin_utama']);
        $file = UploadedFile::fake()->create('icon.svg', 10, 'image/svg+xml');
        $this->actingAs($admin)->postJson('/api/homepage-settings/upload-media', ['file' => $file])->assertStatus(422);
    }

    public function test_homepage_media_upload_accepts_jpg(): void
    {
        $admin = User::factory()->create(['role' => 'admin_utama']);
        Storage::fake('public');
        $file = UploadedFile::fake()->image('hero.jpg', 200, 120);
        $this->actingAs($admin)->post('/api/homepage-settings/upload-media', ['file' => $file])->assertOk();
    }

    public function test_public_homepage_endpoint_exposes_default_prayer_location(): void
    {
        SiteSetting::create(['app_name' => 'Test LPI']);
        $this->getJson('/api/homepage-settings/public')
            ->assertOk()
            ->assertJsonPath('prayer_city_name', 'Banda Aceh')
            ->assertJsonPath('prayer_timezone', 'Asia/Jakarta')
            ->assertJsonPath('prayer_card_visible', true);
    }

    public function test_students_pagination(): void
    {
        $admin = User::factory()->create(['role' => 'admin_utama']);
        $cl = Classroom::create(['name' => 'X-1', 'grade' => 10, 'academic_year' => '2026/2027']);
        for ($i = 1; $i <= 20; $i++) {
            Student::create(['nis' => "N{$i}", 'nisn' => "NN{$i}", 'name' => "Siswa {$i}", 'gender' => 'L', 'classroom_id' => $cl->id, 'status' => 'aktif']);
        }
        $res = $this->actingAs($admin)->getJson('/api/students')->assertOk();
        $json = $res->json();
        $this->assertArrayHasKey('data', $json);
        $data = $json['data'];
        $total = $json['total'] ?? $json['meta']['total'] ?? null;
        $this->assertNotNull($total, 'pagination total missing: '.json_encode(array_keys($json)));
        $this->assertEquals(15, count($data));
        $this->assertEquals(20, (int) $total);
    }

    public function test_backup_contains_sql_structure(): void
    {
        $admin = User::factory()->create(['role' => 'admin_utama']);
        Storage::fake('backups');
        $this->actingAs($admin)->postJson('/api/backups')->assertOk();
        $files = Storage::disk('backups')->allFiles();
        $this->assertNotEmpty($files);
        $gz = Storage::disk('backups')->get($files[0]);
        $sql = gzdecode($gz);
        $this->assertStringContainsString('CREATE TABLE', $sql);
        $this->assertStringNotContainsString('APP_KEY', $sql);
    }
}
