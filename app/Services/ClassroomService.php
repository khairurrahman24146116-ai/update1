<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Scopes\BerdasarkanUnit;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class ClassroomService
{
    public function list(?string $search = null, int $perPage = 15, ?int $yearId = null): LengthAwarePaginator
    {
        return Classroom::query()->withCount('students')
            ->when($yearId, fn ($q) => $q->where('tahun_ajaran_id', $yearId))
            ->when($search, fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('academic_year', 'like', "%{$search}%"))
            ->orderBy('grade')->orderBy('name')
            ->paginate($perPage);
    }

    public function all(): Collection
    {
        return Classroom::orderBy('grade')->orderBy('name')->get();
    }

    public function find(int $id): Classroom
    {
        return Classroom::withoutGlobalScope(BerdasarkanUnit::class)->findOrFail($id);
    }

    public function create(array $data): Classroom
    {
        return Classroom::create($this->prepare($data));
    }

    public function update(Classroom $classroom, array $data): Classroom
    {
        $classroom->update($this->prepare($data, $classroom));

        return $classroom->fresh();
    }

    public function delete(Classroom $classroom): void
    {
        if ($classroom->students()->exists()) {
            abort(409, 'Kelas masih memiliki siswa');
        }
        $classroom->delete();
    }

    /**
     * Menyelaraskan unit, jalur, jenjang, dan tahun ajaran agar konsisten.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function prepare(array $data, ?Classroom $classroom = null): array
    {
        $yearId = $data['tahun_ajaran_id'] ?? null;

        if ($yearId) {
            $year = AcademicYear::find($yearId);

            if ($year) {
                $data['academic_year'] = $year->name;
            }
        } elseif (! empty($data['academic_year'])) {
            $year = AcademicYear::firstOrCreate(
                ['name' => $data['academic_year']],
                ['is_active' => false],
            );
            $data['tahun_ajaran_id'] = $year->id;
        }

        $unit = $data['unit'] ?? $classroom?->unit;
        $jalur = $data['jalur'] ?? $classroom?->jalur;
        $grade = isset($data['grade']) ? (int) $data['grade'] : $classroom?->grade;
        $jenjang = $data['jenjang'] ?? $classroom?->jenjang;

        if (! $jalur && $unit) {
            $jalur = $unit === 'lpi' ? Classroom::JALUR_LPI : Classroom::JALUR_FORMAL;
        }

        if (! $unit && $jalur) {
            $unit = $jalur === Classroom::JALUR_LPI ? 'lpi' : 'sma';
        }

        if (! $jenjang && $grade !== null) {
            $jenjang = $this->jenjangFromLegacy((string) $unit, (int) $grade);
        }

        if ($unit !== null) {
            $data['unit'] = $unit;
        }
        if ($jalur !== null) {
            $data['jalur'] = $jalur;
        }
        if ($jenjang !== null) {
            $data['jenjang'] = $jenjang;
        }

        return $data;
    }

    private function jenjangFromLegacy(string $unit, int $grade): string
    {
        if ($unit === 'lpi') {
            return $grade >= 10 ? Classroom::JENJANG_ALIYAH : Classroom::JENJANG_TSANAWIYAH;
        }

        return $grade >= 10 ? Classroom::JENJANG_SMA : Classroom::JENJANG_SMP;
    }
}
