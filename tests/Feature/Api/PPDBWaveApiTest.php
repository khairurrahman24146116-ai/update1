<?php

namespace Tests\Feature\Api;

use App\Models\PPDBRegistration;
use App\Models\PPDBWave;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PPDBWaveApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_delete_removes_wave_from_database(): void
    {
        $admin = User::factory()->create(['role' => 'admin_utama']);
        $wave = $this->createWave();

        $this->actingAs($admin)
            ->deleteJson('/api/ppdb-waves/'.$wave->id)
            ->assertNoContent();

        $this->assertModelMissing($wave);
    }

    public function test_delete_returns_409_when_wave_has_registrations(): void
    {
        $admin = User::factory()->create(['role' => 'admin_utama']);
        $wave = $this->createWave();
        PPDBRegistration::create(['wave_id' => $wave->id, 'name' => 'Siswa Uji']);

        $this->actingAs($admin)
            ->deleteJson('/api/ppdb-waves/'.$wave->id)
            ->assertConflict()
            ->assertJsonPath('message', 'Gelombang masih memiliki pendaftar');

        $this->assertModelExists($wave);
    }

    public function test_update_persists_changes_without_creating_a_second_row(): void
    {
        $admin = User::factory()->create(['role' => 'admin_utama']);
        $wave = $this->createWave();

        $this->actingAs($admin)
            ->putJson('/api/ppdb-waves/'.$wave->id, ['name' => 'Gelombang 2', 'status' => 'tutup'])
            ->assertOk()
            ->assertJsonPath('name', 'Gelombang 2');

        $this->assertDatabaseCount('p_p_d_b_waves', 1);
        $this->assertSame('Gelombang 2', $wave->fresh()->name);
        $this->assertSame('tutup', $wave->fresh()->status);
    }

    public function test_show_returns_404_when_wave_is_missing(): void
    {
        $admin = User::factory()->create(['role' => 'admin_utama']);

        $this->actingAs($admin)
            ->getJson('/api/ppdb-waves/999999')
            ->assertNotFound();
    }

    public function test_create_returns_201_and_persists_wave(): void
    {
        $admin = User::factory()->create(['role' => 'admin_utama']);

        $this->actingAs($admin)
            ->postJson('/api/ppdb-waves', ['name' => 'Gelombang Baru', 'status' => 'buka'])
            ->assertCreated()
            ->assertJsonPath('name', 'Gelombang Baru');

        $this->assertDatabaseCount('p_p_d_b_waves', 1);
    }

    private function createWave(): PPDBWave
    {
        return PPDBWave::create([
            'name' => 'Gelombang 1',
            'start_date' => '2026-01-01',
            'end_date' => '2026-06-30',
            'status' => 'buka',
            'is_public' => true,
        ]);
    }
}
