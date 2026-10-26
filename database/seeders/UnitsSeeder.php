<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Seeder;

class UnitsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ([
            ['kode' => 'LPI', 'nama' => 'LPI Dayah Madani Al-Aziziyah'],
            ['kode' => 'SMA', 'nama' => 'SMA Dayah Madani Al-Aziziyah'],
            ['kode' => 'SMP', 'nama' => 'SMP Dayah Madani Al-Aziziyah'],
        ] as $unit) {
            Unit::updateOrCreate(['kode' => $unit['kode']], ['nama' => $unit['nama']]);
        }
    }
}
