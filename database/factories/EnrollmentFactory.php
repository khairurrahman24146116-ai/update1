<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Enrollment>
 */
class EnrollmentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'class_id' => Classroom::factory(),
            'tahun_ajaran_id' => AcademicYear::factory(),
            'jalur' => Enrollment::JALUR_FORMAL,
            'status' => Enrollment::STATUS_AKTIF,
        ];
    }
}
