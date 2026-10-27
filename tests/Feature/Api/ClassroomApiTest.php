<?php

namespace Tests\Feature\Api;

use App\Models\Classroom;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClassroomApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_classroom(): void
    {
        $admin = User::factory()->create(['role' => 'admin_utama']);

        $this->actingAs($admin)
            ->postJson('/api/classrooms', [
                'name' => 'X-1',
                'grade' => 10,
            ])
            ->assertStatus(201)
            ->assertJsonPath('name', 'X-1');
    }

    public function test_non_admin_cannot_create_classroom(): void
    {
        $guru = User::factory()->create(['role' => 'guru']);

        $this->actingAs($guru)
            ->postJson('/api/classrooms', [
                'name' => 'X-2',
                'grade' => 10,
            ])
            ->assertStatus(403);
    }

    public function test_bendahara_only_sees_classrooms_of_own_unit(): void
    {
        Classroom::create(['name' => 'X-SMA', 'grade' => 10, 'academic_year' => '2026/2027', 'unit' => 'sma']);
        Classroom::create(['name' => 'X-LPI', 'grade' => 7, 'academic_year' => '2026/2027', 'unit' => 'lpi']);

        $bendahara = User::factory()->create(['role' => 'bendahara_lpi', 'unit' => null]);
        $bendahara->units()->attach(Unit::where('kode', 'LPI')->value('id'));

        $res = $this->actingAs($bendahara)->getJson('/api/classrooms')->assertOk();
        $names = collect($res->json('data'))->pluck('name')->all();

        $this->assertSame(['X-LPI'], $names);
    }

    public function test_bendahara_cannot_view_other_unit_classroom(): void
    {
        $classroom = Classroom::create(['name' => 'X-SMA', 'grade' => 10, 'academic_year' => '2026/2027', 'unit' => 'sma']);
        $bendahara = User::factory()->create(['role' => 'bendahara_lpi', 'unit' => null]);
        $bendahara->units()->attach(Unit::where('kode', 'LPI')->value('id'));

        $this->actingAs($bendahara)->getJson("/api/classrooms/{$classroom->id}")->assertStatus(403);
    }
}
