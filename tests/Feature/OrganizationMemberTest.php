<?php

namespace Tests\Feature;

use App\Models\OrganizationMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OrganizationMemberTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_endpoint_lists_only_active_members(): void
    {
        OrganizationMember::create(['position' => 'Pimpinan Dayah', 'name' => 'Abiya', 'is_active' => true]);
        OrganizationMember::create(['position' => 'Disembunyikan', 'name' => 'X', 'is_active' => false]);

        $members = $this->getJson('/api/organization-members/public')
            ->assertOk()
            ->json('members');

        $this->assertCount(1, $members);
        $this->assertSame('Pimpinan Dayah', $members[0]['position']);
        $this->assertArrayHasKey('parent_id', $members[0]);
        $this->assertArrayHasKey('photo', $members[0]);
        $this->assertArrayHasKey('is_confirmed', $members[0]);
    }

    public function test_guests_cannot_manage_organization(): void
    {
        $this->postJson('/api/organization-members', ['position' => 'X'])->assertStatus(401);
    }

    public function test_non_admin_cannot_manage_organization(): void
    {
        $guru = User::factory()->create(['role' => 'guru']);

        $this->actingAs($guru)->getJson('/api/organization-members')->assertStatus(403);
        $this->actingAs($guru)->postJson('/api/organization-members', ['position' => 'X'])->assertStatus(403);
    }

    public function test_admin_can_manage_organization_members(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $res = $this->actingAs($admin)->postJson('/api/organization-members', [
            'position' => 'Pimpinan Dayah',
            'name' => 'Abiya',
        ])->assertStatus(201);

        $id = $res->json('member.id');
        $this->assertNotNull($id);

        $this->actingAs($admin)->putJson("/api/organization-members/{$id}", [
            'position' => 'Pimpinan Dayah Madani',
            'name' => 'Abiya',
            'is_confirmed' => true,
        ])->assertOk()->assertJsonPath('member.position', 'Pimpinan Dayah Madani');

        $this->actingAs($admin)->deleteJson("/api/organization-members/{$id}")->assertOk();

        $this->assertDatabaseMissing('organization_members', ['id' => $id]);
    }

    public function test_update_prevents_hierarchy_cycle(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $parent = OrganizationMember::create(['position' => 'Pimpinan']);
        $child = OrganizationMember::create(['position' => 'Bawahan', 'parent_id' => $parent->id]);

        $this->actingAs($admin)->putJson("/api/organization-members/{$parent->id}", [
            'position' => 'Pimpinan',
            'parent_id' => $child->id,
        ])->assertStatus(422);
    }

    public function test_photo_upload_rejects_svg_and_accepts_jpg(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $svg = UploadedFile::fake()->create('icon.svg', 10, 'image/svg+xml');
        $this->actingAs($admin)
            ->postJson('/api/organization-members/upload-photo', ['file' => $svg])
            ->assertStatus(422);

        Storage::fake('public');
        $jpg = UploadedFile::fake()->image('photo.jpg', 200, 260);
        $this->actingAs($admin)
            ->post('/api/organization-members/upload-photo', ['file' => $jpg])
            ->assertOk()
            ->assertJsonStructure(['path']);
    }
}
