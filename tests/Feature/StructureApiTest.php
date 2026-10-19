<?php

namespace Tests\Feature;

use App\Models\OrganizationMember;
use App\Models\User;
use Database\Seeders\StructureMemberSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StructureApiTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_public_endpoint_returns_tree_for_each_unit(): void
    {
        $lpi = OrganizationMember::create(['unit' => 'lpi', 'position' => 'Pimpinan LPI', 'name' => 'A', 'sort_order' => 1]);
        OrganizationMember::create(['unit' => 'lpi', 'position' => 'Kepala SMP unit lama', 'name' => 'B', 'parent_id' => $lpi->id, 'sort_order' => 1]);
        OrganizationMember::create(['unit' => 'sma', 'position' => 'Kepala SMA', 'name' => 'C', 'sort_order' => 1]);
        OrganizationMember::create(['unit' => 'sma', 'position' => 'Disembunyikan', 'name' => 'D', 'is_active' => false]);

        $lpiTree = $this->getJson('/api/structure/lpi')->assertOk()->json('tree');
        $this->assertCount(1, $lpiTree);
        $this->assertSame('Pimpinan LPI', $lpiTree[0]['position']);
        $this->assertCount(1, $lpiTree[0]['children']);

        $smaTree = $this->getJson('/api/structure/sma')->assertOk()->json('tree');
        $this->assertCount(1, $smaTree);
        $this->assertSame('Kepala SMA', $smaTree[0]['position']);

        $this->getJson('/api/structure/smp')->assertOk()->assertJsonPath('tree', []);
    }

    public function test_public_endpoint_rejects_unknown_unit(): void
    {
        $this->getJson('/api/structure/unknown')->assertStatus(422);
    }

    public function test_admin_endpoints_require_token_and_admin_role(): void
    {
        $this->getJson('/api/structure-members')->assertStatus(401);

        $guru = User::factory()->create(['role' => 'guru']);
        $this->actingAs($guru)->getJson('/api/structure-members')->assertStatus(403);
        $this->actingAs($guru)->postJson('/api/structure-members', ['unit' => 'lpi', 'position' => 'X'])->assertStatus(403);
    }

    public function test_admin_can_manage_structure_members(): void
    {
        $admin = $this->admin();

        $root = $this->actingAs($admin)->postJson('/api/structure-members', [
            'unit' => 'sma',
            'position' => 'Kepala Sekolah',
            'name' => 'Fahmi',
        ])->assertStatus(201)->json('member');

        $this->actingAs($admin)->postJson('/api/structure-members', [
            'unit' => 'sma',
            'position' => 'Wakil Kurikulum',
            'parent_id' => $root['id'],
        ])->assertStatus(201);

        $this->actingAs($admin)->getJson('/api/structure-members?unit=sma')
            ->assertOk()
            ->assertJsonCount(2, 'members');

        $this->actingAs($admin)->putJson("/api/structure-members/{$root['id']}", [
            'position' => 'Kepala SMA',
            'name' => 'Fahmi',
            'is_active' => true,
        ])->assertOk()->assertJsonPath('member.position', 'Kepala SMA');
    }

    public function test_update_prevents_hierarchy_cycle(): void
    {
        $admin = $this->admin();
        $parent = OrganizationMember::create(['unit' => 'lpi', 'position' => 'Pimpinan']);
        $child = OrganizationMember::create(['unit' => 'lpi', 'position' => 'Bawahan', 'parent_id' => $parent->id]);

        $this->actingAs($admin)->putJson("/api/structure-members/{$parent->id}", [
            'position' => 'Pimpinan',
            'parent_id' => $child->id,
        ])->assertStatus(422);
    }

    public function test_parent_must_belong_to_same_unit(): void
    {
        $admin = $this->admin();
        $sma = OrganizationMember::create(['unit' => 'sma', 'position' => 'Kepala SMA']);

        $this->actingAs($admin)->postJson('/api/structure-members', [
            'unit' => 'lpi',
            'position' => 'Anggota LPI',
            'parent_id' => $sma->id,
        ])->assertStatus(422);
    }

    public function test_depth_cannot_exceed_three_levels_below_root(): void
    {
        $admin = $this->admin();
        $root = OrganizationMember::create(['unit' => 'lpi', 'position' => 'L0']);
        $l1 = OrganizationMember::create(['unit' => 'lpi', 'position' => 'L1', 'parent_id' => $root->id]);
        $l2 = OrganizationMember::create(['unit' => 'lpi', 'position' => 'L2', 'parent_id' => $l1->id]);

        $l3 = $this->actingAs($admin)->postJson('/api/structure-members', [
            'unit' => 'lpi',
            'position' => 'L3',
            'parent_id' => $l2->id,
        ])->assertStatus(201)->json('member');

        $this->actingAs($admin)->postJson('/api/structure-members', [
            'unit' => 'lpi',
            'position' => 'L4',
            'parent_id' => $l3['id'],
        ])->assertStatus(422);
    }

    public function test_photo_upload_rejects_svg_and_accepts_jpg(): void
    {
        $admin = $this->admin();

        $svg = UploadedFile::fake()->create('icon.svg', 10, 'image/svg+xml');
        $this->actingAs($admin)
            ->postJson('/api/structure-members/upload-photo', ['file' => $svg])
            ->assertStatus(422);

        Storage::fake('public');
        $jpg = UploadedFile::fake()->image('photo.jpg', 200, 260);
        $this->actingAs($admin)
            ->post('/api/structure-members/upload-photo', ['file' => $jpg])
            ->assertOk()
            ->assertJsonStructure(['path']);
    }

    public function test_structure_seeder_is_idempotent(): void
    {
        $this->seed(StructureMemberSeeder::class);
        $first = OrganizationMember::query()->count();

        $this->seed(StructureMemberSeeder::class);
        $second = OrganizationMember::query()->count();

        $this->assertSame($first, $second);
        $this->assertSame(1, OrganizationMember::query()->unit('sma')->count());
        $this->assertSame(0, OrganizationMember::query()->unit('smp')->count());
    }
}
