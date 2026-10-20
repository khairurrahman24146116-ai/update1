<?php

namespace Database\Factories;

use App\Models\Classroom;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Student>
 */
class StudentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nis' => fake()->unique()->numerify('NIS####'),
            'nisn' => fake()->unique()->numerify('##########'),
            'gender' => fake()->randomElement(['L', 'P']),
            'name' => fake()->name(),
            'classroom_id' => Classroom::factory(),
            'parent_id' => null,
            'status' => 'aktif',
            'asal_siswa' => 'internal',
        ];
    }
}
