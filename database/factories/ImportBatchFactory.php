<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\ImportBatch;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ImportBatch>
 */
class ImportBatchFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'file_name' => 'import-siswa.xlsx',
            'diimport_oleh' => User::factory()->state(['role' => 'admin']),
            'tahun_ajaran_id' => AcademicYear::factory(),
            'total_baris' => 0,
            'berhasil' => 0,
            'gagal' => 0,
            'peringatan' => 0,
            'update_existing' => false,
            'status' => ImportBatch::STATUS_PREVIEW,
        ];
    }
}
