<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subject_scopes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->string('unit', 10);
            $table->string('jenjang', 20);
            $table->timestamps();

            $table->unique(['subject_id', 'unit', 'jenjang']);
            $table->index(['unit', 'jenjang']);
        });

        $this->backfillScopes();
    }

    public function down(): void
    {
        Schema::dropIfExists('subject_scopes');
    }

    private function backfillScopes(): void
    {
        $now = now();
        $fallbackScopes = $this->classroomScopes();

        if ($fallbackScopes === []) {
            $fallbackScopes = [
                ['unit' => 'lpi', 'jenjang' => 'Tsanawiyah'],
                ['unit' => 'lpi', 'jenjang' => 'Aliyah'],
                ['unit' => 'sma', 'jenjang' => 'SMA'],
                ['unit' => 'smp', 'jenjang' => 'SMP'],
            ];
        }

        $subjects = DB::table('subjects')->pluck('id');

        foreach ($subjects as $subjectId) {
            $scopes = $this->usedScopes((int) $subjectId);

            if ($scopes === []) {
                $scopes = $fallbackScopes;
            }

            $rows = array_map(fn (array $scope): array => [
                'subject_id' => $subjectId,
                'unit' => $scope['unit'],
                'jenjang' => $scope['jenjang'],
                'created_at' => $now,
                'updated_at' => $now,
            ], $scopes);

            DB::table('subject_scopes')->insertOrIgnore($rows);
        }
    }

    /**
     * @return list<array{unit: string, jenjang: string}>
     */
    private function classroomScopes(): array
    {
        return DB::table('classrooms')
            ->get(['unit', 'jenjang', 'grade'])
            ->map(fn (object $classroom): array => $this->normalizeScope(
                $classroom->unit,
                $classroom->jenjang,
                $classroom->grade,
            ))
            ->unique(fn (array $scope): string => $scope['unit'].':'.$scope['jenjang'])
            ->values()
            ->all();
    }

    /**
     * @return list<array{unit: string, jenjang: string}>
     */
    private function usedScopes(int $subjectId): array
    {
        $classrooms = DB::table('teacher_subjects')
            ->join('classrooms', 'classrooms.id', '=', 'teacher_subjects.classroom_id')
            ->where('teacher_subjects.subject_id', $subjectId)
            ->get(['classrooms.unit', 'classrooms.jenjang', 'classrooms.grade'])
            ->merge(
                DB::table('schedules')
                    ->join('classrooms', 'classrooms.id', '=', 'schedules.classroom_id')
                    ->where('schedules.subject_id', $subjectId)
                    ->get(['classrooms.unit', 'classrooms.jenjang', 'classrooms.grade'])
            );

        return $classrooms
            ->map(fn (object $classroom): array => $this->normalizeScope(
                $classroom->unit,
                $classroom->jenjang,
                $classroom->grade,
            ))
            ->unique(fn (array $scope): string => $scope['unit'].':'.$scope['jenjang'])
            ->values()
            ->all();
    }

    /**
     * @return array{unit: string, jenjang: string}
     */
    private function normalizeScope(?string $unit, ?string $jenjang, mixed $grade): array
    {
        $unit = strtolower(trim((string) $unit)) ?: 'sma';
        $jenjang = trim((string) $jenjang);

        if ($jenjang === '') {
            $grade = (int) $grade;
            $jenjang = match ($unit) {
                'lpi' => $grade >= 10 ? 'Aliyah' : 'Tsanawiyah',
                default => $grade >= 10 ? 'SMA' : 'SMP',
            };
        }

        return ['unit' => $unit, 'jenjang' => $jenjang];
    }
};
