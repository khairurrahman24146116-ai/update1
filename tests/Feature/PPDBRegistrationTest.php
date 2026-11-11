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
