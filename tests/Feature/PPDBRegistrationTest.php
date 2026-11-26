<?php

namespace Tests\Feature;

use App\Models\PPDBRegistration;
use App\Models\PPDBWave;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PPDBRegistrationTest extends TestCase
{
    use RefreshDatabase;

    private function wave(): PPDBWave
    {
        return PPDBWave::create([
            'name' => 'Gelombang 1 2026',
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'status' => 'buka',
            'is_public' => true,
        ]);
    }

    private function payload(array $over = []): array
    {
        return array_merge([
            'jenjang' => 'sma',
            'name' => 'Ahmad Santri',
            'nik' => '1101010101010101',
            'gender' => 'L',
            'birth_place' => 'Banda Aceh',
            'birth_date' => '2010-01-01',
            'nationality' => 'Indonesia',
            'child_order' => 1,
            'sibling_count' => 2,
            'orphan_status' => 'Bukan Yatim',
            'daily_language' => 'Bahasa Indonesia',
            'address' => 'Jl. Dayah No. 1',
            'village' => 'Lampeuneurut',
            'district' => 'Darul Imarah',
            'city' => 'Aceh Besar',
            'province' => 'Aceh',
            'origin_school_name' => 'SDN 1 Banda Aceh',
            'father_family_card' => '1101010101010101',
            'father_name' => 'Ayah Ahmad',
            'mother_family_card' => '1101010101010102',
            'mother_name' => 'Ibu Ahmad',
            'financier_name' => 'Ayah Ahmad',
            'financier_relation' => 'Ayah',
            'emergency_name' => 'Paman Ahmad',
            'emergency_relation' => 'Paman',
            'emergency_phone' => '081234567890',
            'guardian_name' => 'Wali Ahmad',
            'guardian_relation' => 'Paman',
            'blood_type' => 'O',
            'declarant_name' => 'Ayah Ahmad',
            'consent' => '1',
        ], $over);
    }

    public function test_public_store_creates_registration_with_number(): void
    {
        Storage::fake('local');
        $wave = $this->wave();

        $res = $this->post('/api/ppdb-registrations', array_merge(
            $this->payload(['wave_id' => $wave->id]),
            ['photo' => UploadedFile::fake()->image('foto.jpg', 300, 400)]
        ));

        $res->assertCreated()->assertJsonPath('status', 'pending');
        $this->assertNotEmpty($res->json('registration_number'));
        $this->assertDatabaseHas('p_p_d_b_registrations', [
            'wave_id' => $wave->id,
            'nik' => '1101010101010101',
            'status' => 'pending',
        ]);

        $registration = PPDBRegistration::firstOrFail();
        $this->assertNotNull($registration->photo_path);
        Storage::disk('local')->assertExists($registration->photo_path);
    }

    public function test_photo_download_requires_authentication_and_permission(): void
    {
        Storage::fake('local');
        $wave = $this->wave();

        $this->post('/api/ppdb-registrations', array_merge(
            $this->payload(['wave_id' => $wave->id]),
            ['photo' => UploadedFile::fake()->image('foto.jpg', 300, 400)]
        ))->assertCreated();

        $registration = PPDBRegistration::firstOrFail();
        $url = "/api/ppdb-registrations/{$registration->id}/photo";

        $this->getJson($url)->assertUnauthorized();

        $guru = User::factory()->create(['role' => User::ROLE_GURU]);
        $this->actingAs($guru)->get($url)->assertForbidden();

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN_UTAMA]);
        $this->actingAs($admin)->get($url)->assertOk();
    }

    public function test_public_store_rejects_invalid_nik(): void
    {
        Storage::fake('local');
        $wave = $this->wave();

        $res = $this->post('/api/ppdb-registrations', array_merge(
            $this->payload(['wave_id' => $wave->id, 'nik' => '123']),
            ['photo' => UploadedFile::fake()->image('foto.jpg', 300, 400)]
        ));

        $res->assertUnprocessable();
        $this->assertDatabaseCount('p_p_d_b_registrations', 0);
    }

    public function test_public_store_rejects_closed_wave(): void
    {
        Storage::fake('local');
        $wave = $this->wave();
        $wave->update(['status' => 'tutup']);

        $res = $this->post('/api/ppdb-registrations', array_merge(
            $this->payload(['wave_id' => $wave->id]),
            ['photo' => UploadedFile::fake()->image('foto.jpg', 300, 400)]
        ));

        $res->assertStatus(422);
        $this->assertDatabaseCount('p_p_d_b_registrations', 0);
    }

    public function test_salafiyah_requires_female_gender(): void
    {
        Storage::fake('local');
        $wave = $this->wave();

        $this->post('/api/ppdb-registrations', array_merge(
            $this->payload(['wave_id' => $wave->id, 'jenjang' => 'salafiyah', 'gender' => 'L']),
            ['photo' => UploadedFile::fake()->image('foto.jpg', 300, 400)]
        ))->assertUnprocessable();

        $this->assertDatabaseCount('p_p_d_b_registrations', 0);

        $this->post('/api/ppdb-registrations', array_merge(
            $this->payload(['wave_id' => $wave->id, 'jenjang' => 'salafiyah', 'gender' => 'P']),
            ['photo' => UploadedFile::fake()->image('foto.jpg', 300, 400)]
        ))->assertCreated();

        $this->assertDatabaseHas('p_p_d_b_registrations', [
            'wave_id' => $wave->id,
            'jenjang' => 'salafiyah',
            'gender' => 'P',
        ]);
    }

    private function registration(PPDBWave $wave, array $over = []): PPDBRegistration
    {
        return PPDBRegistration::create(array_merge([
            'wave_id' => $wave->id,
            'registration_number' => 'PPDB-2026-001-0001',
            'jenjang' => 'sma',
            'name' => 'Ahmad Santri',
            'nik' => '1101010101010101',
            'status' => 'pending',
        ], $over));
    }

    public function test_public_can_check_status_by_registration_number(): void
    {
        $wave = $this->wave();
        $registration = $this->registration($wave, ['status' => 'diterima']);

        $this->postJson('/api/ppdb-registrations/check-status', [
            'registration_number' => $registration->registration_number,
        ])
            ->assertOk()
            ->assertJsonPath('registration_number', $registration->registration_number)
            ->assertJsonPath('status', 'diterima')
            ->assertJsonPath('status_label', 'Diterima')
            ->assertJsonPath('jenjang_label', 'Pendaftaran SMA');
    }

    public function test_public_can_check_status_by_nik(): void
    {
        $wave = $this->wave();
        $this->registration($wave, ['nik' => '1101010101010101', 'status' => 'cadangan']);

        $this->postJson('/api/ppdb-registrations/check-status', [
            'nik' => '1101010101010101',
        ])
            ->assertOk()
            ->assertJsonPath('status', 'cadangan')
            ->assertJsonPath('status_label', 'Cadangan');
    }

    public function test_public_status_check_requires_an_identifier(): void
    {
        $this->postJson('/api/ppdb-registrations/check-status', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['registration_number', 'nik']);
    }

    public function test_public_status_check_rejects_unknown_registration(): void
    {
        $this->postJson('/api/ppdb-registrations/check-status', [
            'registration_number' => 'PPDB-TIDAK-ADA',
        ])->assertUnprocessable();
    }

    public function test_public_status_check_requires_both_identifiers_to_match(): void
    {
        $wave = $this->wave();
        $this->registration($wave, [
            'registration_number' => 'PPDB-2026-001-0001',
            'nik' => '1101010101010101',
        ]);

        $this->postJson('/api/ppdb-registrations/check-status', [
            'registration_number' => 'PPDB-2026-001-0001',
            'nik' => '1101010101010199',
        ])->assertUnprocessable();
    }

    public function test_public_announcements_only_lists_accepted_registrations(): void
    {
        $wave = $this->wave();
        $this->registration($wave, ['status' => 'diterima', 'name' => 'Diterima Satu']);
        $this->registration($wave, ['status' => 'pending', 'name' => 'Pending Dua', 'nik' => '1101010101010102', 'registration_number' => 'PPDB-2026-001-0002']);

        $this->getJson('/api/ppdb-registrations/public-announcements')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Diterima Satu')
            ->assertJsonPath('meta.total', 1);
    }

    public function test_public_announcements_can_filter_by_jenjang(): void
    {
        $wave = $this->wave();
        $this->registration($wave, ['status' => 'diterima', 'jenjang' => 'sma', 'name' => 'SMA Satu']);
        $this->registration($wave, ['status' => 'diterima', 'jenjang' => 'smp', 'name' => 'SMP Dua', 'nik' => '1101010101010102', 'registration_number' => 'PPDB-2026-001-0002']);

        $this->getJson('/api/ppdb-registrations/public-announcements?jenjang=smp')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.jenjang', 'smp');
    }

    public function test_admin_can_download_registration_pdf(): void
    {
        Storage::fake('local');
        $wave = $this->wave();

        $this->post('/api/ppdb-registrations', array_merge(
            $this->payload(['wave_id' => $wave->id]),
            ['photo' => UploadedFile::fake()->image('foto.jpg', 300, 400)]
        ))->assertCreated();

        $registration = PPDBRegistration::firstOrFail();
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN_UTAMA]);

        $response = $this->actingAs($admin)->get("/api/ppdb-registrations/{$registration->id}/pdf");

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));
    }

    public function test_admin_can_download_registration_recap_pdf(): void
    {
        Storage::fake('local');
        $wave = $this->wave();

        $this->post('/api/ppdb-registrations', array_merge(
            $this->payload(['wave_id' => $wave->id]),
            ['photo' => UploadedFile::fake()->image('foto.jpg', 300, 400)]
        ))->assertCreated();

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN_UTAMA]);

        $response = $this->actingAs($admin)->get('/api/ppdb-registrations/pdf?wave_id='.$wave->id.'&jenjang=sma');

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));
    }

    public function test_ppdb_pdf_requires_export_permission(): void
    {
        Storage::fake('local');
        $wave = $this->wave();

        $this->post('/api/ppdb-registrations', array_merge(
            $this->payload(['wave_id' => $wave->id]),
            ['photo' => UploadedFile::fake()->image('foto.jpg', 300, 400)]
        ))->assertCreated();

        $registration = PPDBRegistration::firstOrFail();
        $guru = User::factory()->create(['role' => User::ROLE_GURU]);

        $this->actingAs($guru)->get("/api/ppdb-registrations/{$registration->id}/pdf")->assertForbidden();
        $this->actingAs($guru)->get('/api/ppdb-registrations/pdf')->assertForbidden();
    }

    public function test_public_store_rejects_wrong_photo_type(): void
    {
        Storage::fake('local');
        $wave = $this->wave();

        $res = $this->post('/api/ppdb-registrations', array_merge(
            $this->payload(['wave_id' => $wave->id]),
            ['photo' => UploadedFile::fake()->create('dok.pdf', 100, 'application/pdf')]
        ));

        $res->assertUnprocessable();
        $this->assertDatabaseCount('p_p_d_b_registrations', 0);
    }
}
