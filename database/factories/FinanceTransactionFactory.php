<?php

namespace Database\Factories;

use App\Models\FinanceTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FinanceTransaction>
 */
class FinanceTransactionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'unit' => 'sma',
            'category_id' => null,
            'type' => FinanceTransaction::TYPE_EXPENSE,
            'source' => FinanceTransaction::SOURCE_MANUAL,
            'transaction_date' => fake()->dateTimeBetween('-3 months')->format('Y-m-d'),
            'amount' => fake()->randomFloat(2, 10000, 2000000),
            'description' => fake()->sentence(4),
            'destination' => fake()->optional()->company(),
            'created_by' => User::factory(),
        ];
    }

    public function income(): static
    {
        return $this->state(fn () => ['type' => FinanceTransaction::TYPE_INCOME]);
    }

    public function expense(): static
    {
        return $this->state(fn () => ['type' => FinanceTransaction::TYPE_EXPENSE]);
    }

    public function unit(string $unit): static
    {
        return $this->state(fn () => ['unit' => $unit]);
    }
}
