<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\Classroom;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Classroom>
 */
class ClassroomFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->bothify('Kelas ##'),
            'grade' => 10,
            'academic_year' => '2026/2027',
            'unit' => 'sma',
            'jalur' => Classroom::JALUR_FORMAL,
            'jenjang' => Classroom::JENJANG_SMA,
            'tahun_ajaran_id' => null,
        ];
    }

    public function forYear(AcademicYear $year): static
    {
        return $this->state(fn () => [
            'tahun_ajaran_id' => $year->id,
            'academic_year' => $year->name,
        ]);
    }

    public function smp(): static
    {
        return $this->state(fn () => [
            'grade' => 7,
            'jenjang' => Classroom::JENJANG_SMP,
            'jalur' => Classroom::JALUR_FORMAL,
            'unit' => 'sma',
        ]);
    }

    public function lpi(string $jenjang = Classroom::JENJANG_ALIYAH, int $grade = 10): static
    {
        return $this->state(fn () => [
            'grade' => $grade,
            'jenjang' => $jenjang,
            'jalur' => Classroom::JALUR_LPI,
            'unit' => 'lpi',
        ]);
    }
}
