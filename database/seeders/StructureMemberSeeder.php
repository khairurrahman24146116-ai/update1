<?php

namespace Database\Seeders;

use App\Models\OrganizationMember;
use Illuminate\Database\Seeder;

class StructureMemberSeeder extends Seeder
{
    /**
     * Seed the initial structure for each unit without duplicating rows.
     *
     * - LPI: the documentation already used by the original LPI chart.
     * - SMA: the school leadership recorded in the existing seeders.
     * - SMP: intentionally left empty ("Segera hadir").
     *
     * No fabricated names, positions, photos, or numbers are inserted, and no
     * account or password data belongs here. Existing rows are never deleted or
     * overwritten: a unit that already has data is left untouched.
     */
    public function run(): void
    {
        $this->seedLpi();
        $this->seedSma();
    }

    private function seedLpi(): void
    {
        if (OrganizationMember::query()->unit(OrganizationMember::UNIT_LPI)->exists()) {
            return;
        }

        $leader = OrganizationMember::updateOrCreate(
            [
                'unit' => OrganizationMember::UNIT_LPI,
                'position' => 'Pimpinan Dayah Madani Al-Aziziyah',
                'name' => 'Abiya Dr. Tgk. H. Muhammad Hatta, Lc., M.Ed.',
            ],
            [
                'parent_id' => null,
                'photo_path' => '/images/profile/4.png',
                'sort_order' => 1,
                'is_confirmed' => true,
                'is_active' => true,
            ],
        );

        OrganizationMember::updateOrCreate(
            [
                'unit' => OrganizationMember::UNIT_LPI,
                'position' => 'Kepala SMA Madani Al-Aziziyah & Sekretaris',
                'name' => 'Fahmi, M.Pd.',
            ],
            [
                'parent_id' => $leader->id,
                'photo_path' => '/images/profile/3.png',
                'sort_order' => 1,
                'is_confirmed' => true,
                'is_active' => true,
            ],
        );

        OrganizationMember::updateOrCreate(
            [
                'unit' => OrganizationMember::UNIT_LPI,
                'position' => 'Kepala SMP Madani Al-Aziziyah',
                'name' => null,
            ],
            [
                'parent_id' => $leader->id,
                'photo_path' => null,
                'sort_order' => 2,
                'is_confirmed' => false,
                'is_active' => true,
            ],
        );
    }

    private function seedSma(): void
    {
        if (OrganizationMember::query()->unit(OrganizationMember::UNIT_SMA)->exists()) {
            return;
        }

        OrganizationMember::updateOrCreate(
            [
                'unit' => OrganizationMember::UNIT_SMA,
                'position' => 'Kepala SMA Madani Al-Aziziyah & Sekretaris',
                'name' => 'Fahmi, M.Pd.',
            ],
            [
                'parent_id' => null,
                'photo_path' => '/images/profile/3.png',
                'sort_order' => 1,
                'is_confirmed' => true,
                'is_active' => true,
            ],
        );
    }
}
