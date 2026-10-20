<?php

namespace Tests\Feature\Api;

use App\Models\ActivityLog;
use App\Models\Classroom;
use App\Models\Score;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScoreApiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Classroom $classroom;

    private Subject $subject;

    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->classroom = Classroom::create(['name' => 'X-1', 'grade' => 10, 'academic_year' => '2026/2027']);
        $this->subject = Subject::create(['name' => 'Fisika', 'code' => 'FIS']);
        $this->student = Student::create([
            'nis' => 'NIS001',
            'nisn' => '0070000001',
            'name' => 'Siswa Satu',
            'gender' => 'L',
            'classroom_id' => $this->classroom->id,
            'status' => 'aktif',
        ]);
    }

    public function test_saving_scores_twice_updates_without_duplicates(): void
    {
        $payload = [
            'classroom_id' => $this->classroom->id,
            'subject_id' => $this->subject->id,
            'semester' => 'ganjil',
            'academic_year' => '2026/2027',
            'rows' => [
                ['student_id' => $this->student->id, 'value' => 80],
            ],
        ];

        $this->actingAs($this->admin)->postJson('/api/scores', $payload)->assertNoContent();
        $this->actingAs($this->admin)->postJson('/api/scores', [
            ...$payload,
            'rows' => [['student_id' => $this->student->id, 'value' => 95]],
        ])->assertNoContent();

        $this->assertDatabaseCount('scores', 1);
        $this->assertEquals(95, (float) Score::first()->value);
        $this->assertSame(2, ActivityLog::query()->where('action', 'score.update')->count());
    }

    public function test_saving_scores_without_academic_year_updates_without_duplicates(): void
    {
        $payload = [
            'classroom_id' => $this->classroom->id,
            'subject_id' => $this->subject->id,
            'semester' => 'ganjil',
            'rows' => [
                ['student_id' => $this->student->id, 'value' => 70],
            ],
        ];

        $this->actingAs($this->admin)->postJson('/api/scores', $payload)->assertNoContent();
        $this->actingAs($this->admin)->postJson('/api/scores', [
            ...$payload,
            'rows' => [['student_id' => $this->student->id, 'value' => 88]],
        ])->assertNoContent();

        $this->assertDatabaseCount('scores', 1);
        $this->assertEquals(88, (float) Score::first()->value);
        $this->assertNull(Score::first()->academic_year);
    }

    public function test_saving_multiple_students_in_one_request(): void
    {
        $students = [$this->student];

        for ($i = 2; $i <= 5; $i++) {
            $students[] = Student::create([
                'nis' => 'NIS00'.$i,
                'nisn' => '007000000'.$i,
                'name' => 'Siswa '.$i,
                'gender' => 'P',
                'classroom_id' => $this->classroom->id,
                'status' => 'aktif',
            ]);
        }

        $this->actingAs($this->admin)->postJson('/api/scores', [
            'classroom_id' => $this->classroom->id,
            'subject_id' => $this->subject->id,
            'semester' => 'genap',
            'academic_year' => '2026/2027',
            'rows' => array_map(fn (Student $s) => ['student_id' => $s->id, 'value' => 75], $students),
        ])->assertNoContent();

        $this->assertDatabaseCount('scores', 5);
    }
}
