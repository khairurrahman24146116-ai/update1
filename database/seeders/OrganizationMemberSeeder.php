<?php

namespace Database\Seeders;

use App\Models\OrganizationMember;
use Illuminate\Database\Seeder;

class OrganizationMemberSeeder extends Seeder
{
    /**
     * Seed the initial organisation structure.
     *
     * Only documented figures are seeded. Positions that could not be
     * verified from an official source are left blank and flagged
     * (`is_confirmed = false`) so an admin can complete them later.
     * This seeder never overwrites an existing structure.
     */
    public function run(): void
    {
        if (OrganizationMember::query()->exists()) {
            return;
        }

        $leader = OrganizationMember::create([
            'parent_id' => null,
            'name' => 'Abiya Dr. Tgk. H. Muhammad Hatta, Lc., M.Ed.',
            'position' => 'Pimpinan Dayah Madani Al-Aziziyah',
            'photo_path' => '/images/profile/4.png',
            'sort_order' => 1,
            'is_confirmed' => true,
            'is_active' => true,
        ]);

        OrganizationMember::create([
            'parent_id' => $leader->id,
            'name' => 'Fahmi, M.Pd.',
            'position' => 'Kepala SMA Madani Al-Aziziyah & Sekretaris',
            'photo_path' => '/images/profile/3.png',
            'sort_order' => 1,
            'is_confirmed' => true,
            'is_active' => true,
        ]);

        OrganizationMember::create([
            'parent_id' => $leader->id,
            'name' => null,
            'position' => 'Kepala SMP Madani Al-Aziziyah',
            'photo_path' => null,
            'sort_order' => 2,
            'is_confirmed' => false,
            'is_active' => true,
        ]);
    }
}
