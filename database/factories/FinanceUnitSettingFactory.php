<?php

namespace Database\Factories;

use App\Models\FinanceUnitSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FinanceUnitSetting>
 */
class FinanceUnitSettingFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'unit' => 'sma',
            'opening_balance' => 0,
        ];
    }
}
