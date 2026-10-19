<?php

namespace Database\Factories;

use App\Models\Classroom;
use App\Models\SppInvoice;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SppInvoice>
 */
class SppInvoiceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'unit' => 'sma',
            'student_id' => function (): int {
                $classroom = Classroom::create([
                    'name' => 'X-'.fake()->unique()->numberBetween(1, 999),
                    'grade' => 10,
                    'academic_year' => '2026/2027',
                    'unit' => 'sma',
                ]);

                return Student::create([
                    'nis' => (string) fake()->unique()->numberBetween(1000000, 9999999),
                    'nisn' => (string) fake()->unique()->numberBetween(1000000000, 9999999999),
                    'name' => fake()->name(),
                    'gender' => fake()->randomElement(['L', 'P']),
                    'classroom_id' => $classroom->id,
                    'status' => 'aktif',
                ])->id;
            },
            'period' => fake()->unique()->dateTimeBetween('-1 year')->format('Y-m'),
            'due_date' => fake()->dateTimeBetween('now', '+1 month')->format('Y-m-d'),
            'amount' => 500000,
            'paid_amount' => 0,
            'status' => SppInvoice::STATUS_UNPAID,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'paid_amount' => $attributes['amount'] ?? 500000,
            'status' => SppInvoice::STATUS_PAID,
        ]);
    }
}
