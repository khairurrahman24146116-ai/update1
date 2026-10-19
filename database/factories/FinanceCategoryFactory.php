<?php

namespace Database\Factories;

use App\Models\FinanceCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FinanceCategory>
 */
class FinanceCategoryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'unit' => 'sma',
            'type' => 'expense',
            'name' => fake()->unique()->words(2, true),
            'is_active' => true,
        ];
    }

    public function income(): static
    {
        return $this->state(fn () => ['type' => 'income']);
    }

    public function expense(): static
    {
        return $this->state(fn () => ['type' => 'expense']);
    }

    public function unit(string $unit): static
    {
        return $this->state(fn () => ['unit' => $unit]);
    }
}
