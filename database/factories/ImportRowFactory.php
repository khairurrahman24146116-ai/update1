<?php

namespace Database\Factories;

use App\Models\ImportBatch;
use App\Models\ImportRow;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ImportRow>
 */
class ImportRowFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'batch_id' => ImportBatch::factory(),
            'nomor_baris' => 2,
            'data_mentah' => [],
            'data_valid' => [],
            'status' => ImportRow::STATUS_VALID,
            'pesan' => [],
        ];
    }
}
