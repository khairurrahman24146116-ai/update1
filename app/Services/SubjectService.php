<?php

namespace App\Services;

use App\Models\Subject;
use App\Models\SubjectScope;
use App\Models\Unit;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SubjectService
{
    /**
     * @param  array{search?: string|null, jenis?: string|null, unit?: string|null, jenjang?: string|null, status?: string|null, active_only?: bool}  $filters
     */
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $jenis = trim((string) ($filters['jenis'] ?? ''));
        $unit = strtolower(trim((string) ($filters['unit'] ?? '')));
        $jenjang = trim((string) ($filters['jenjang'] ?? ''));
        $status = trim((string) ($filters['status'] ?? ''));

        return Subject::query()
            ->with('scopes')
            ->withCount(['teacherSubjects', 'schedules', 'scores'])
            ->when($search !== '', fn ($query) => $query->where(function ($searchQuery) use ($search): void {
                $searchQuery->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            }))
            ->when($jenis !== '', fn ($query) => $query->where('jenis', $jenis))
            ->when($status === 'aktif', fn ($query) => $query->where('is_active', true))
            ->when($status === 'nonaktif', fn ($query) => $query->where('is_active', false))
            ->when((bool) ($filters['active_only'] ?? false), fn ($query) => $query->where('is_active', true))
            ->when($unit !== '' || $jenjang !== '', fn ($query) => $query->whereHas(
                'scopes',
                fn ($scopeQuery) => $scopeQuery
                    ->when($unit !== '', fn ($q) => $q->whereRaw('LOWER(unit) = ?', [$unit]))
                    ->when($jenjang !== '', fn ($q) => $q->whereRaw('LOWER(jenjang) = ?', [strtolower($jenjang)])),
            ))
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function find(int $id): Subject
    {
        return Subject::query()
            ->with('scopes')
            ->withCount(['teacherSubjects', 'schedules', 'scores'])
            ->findOrFail($id);
    }

    /**
     * @param  array{name: string, code?: string|null, jenis: string, is_active?: bool}  $data
     * @param  list<array{unit: string, jenjang: string}>  $placements
     */
    public function create(array $data, array $placements): Subject
    {
        $placements = $this->normalizePlacements($placements);
        $data = $this->normalizeData($data);
        $this->assertNameUnique($data['name'], $placements);

        return DB::transaction(function () use ($data, $placements): Subject {
            $subject = Subject::create($data);
            $this->replaceScopes($subject, $placements);

            return $subject->fresh(['scopes']);
        });
    }

    /**
     * @param  array{name?: string, code?: string|null, jenis?: string, is_active?: bool}  $data
     * @param  list<array{unit: string, jenjang: string}>|null  $placements
     */
    public function update(Subject $subject, array $data, ?array $placements = null): Subject
    {
        $data = $this->normalizeData($data);
        $name = $data['name'] ?? $subject->name;
        $normalizedPlacements = $placements === null
            ? $subject->scopes->map(fn (SubjectScope $scope): array => [
                'unit' => $scope->unit,
                'jenjang' => $scope->jenjang,
            ])->all()
            : $this->normalizePlacements($placements);

        $this->assertNameUnique($name, $normalizedPlacements, $subject->id);

        return DB::transaction(function () use ($subject, $data, $placements, $normalizedPlacements): Subject {
            $subject->update($data);

            if ($placements !== null) {
                $this->replaceScopes($subject, $this->withHiddenScopes($subject, $normalizedPlacements));
            }

            return $subject->fresh(['scopes']);
        });
    }

    public function delete(Subject $subject): void
    {
        $usage = $subject->usageSummary();
        $total = array_sum($usage);

        if ($total > 0) {
            $parts = [];

            if ($usage['assignments'] > 0) {
                $parts[] = "{$usage['assignments']} penugasan guru";
            }

            if ($usage['schedules'] > 0) {
                $parts[] = "{$usage['schedules']} jadwal/absensi";
            }

            if ($usage['scores'] > 0) {
                $parts[] = "{$usage['scores']} nilai";
            }

            abort(409, "Mapel {$subject->name} sudah dipakai (".implode(', ', $parts).'). Nonaktifkan saja agar data lama tetap aman.');
        }

        $subject->delete();
    }

    /**
     * @param  list<array{unit: string, jenjang: string}>  $placements
     * @return list<array{unit: string, jenjang: string}>
     */
    private function withHiddenScopes(Subject $subject, array $placements): array
    {
        $enabled = Unit::enabledCodes();
        $hidden = $subject->scopes
            ->filter(fn (SubjectScope $scope): bool => ! in_array(strtolower($scope->unit), $enabled, true))
            ->map(fn (SubjectScope $scope): array => [
                'unit' => $scope->unit,
                'jenjang' => $scope->jenjang,
            ])
            ->values()
            ->all();

        return collect([...$placements, ...$hidden])
            ->unique(fn (array $placement): string => strtolower($placement['unit']).':'.strtolower($placement['jenjang']))
            ->values()
            ->all();
    }

    /**
     * @param  list<array{unit: string, jenjang: string}>  $placements
     */
    private function replaceScopes(Subject $subject, array $placements): void
    {
        $subject->scopes()->delete();

        $subject->scopes()->createMany(array_map(fn (array $placement): array => [
            'unit' => $placement['unit'],
            'jenjang' => $placement['jenjang'],
        ], $placements));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizeData(array $data): array
    {
        if (array_key_exists('name', $data)) {
            $data['name'] = trim((string) $data['name']);
        }

        if (array_key_exists('code', $data)) {
            $code = trim((string) $data['code']);
            $data['code'] = $code === '' ? null : strtoupper($code);
        }

        return $data;
    }

    /**
     * @param  list<array{unit: string, jenjang: string}>  $placements
     * @return list<array{unit: string, jenjang: string}>
     */
    private function normalizePlacements(array $placements): array
    {
        $normalized = collect($placements)
            ->map(fn (array $placement): array => [
                'unit' => strtolower(trim((string) ($placement['unit'] ?? ''))),
                'jenjang' => trim((string) ($placement['jenjang'] ?? '')),
            ])
            ->filter(fn (array $placement): bool => $placement['unit'] !== '' && $placement['jenjang'] !== '')
            ->unique(fn (array $placement): string => $placement['unit'].':'.strtolower($placement['jenjang']))
            ->values()
            ->all();

        if ($normalized === []) {
            throw ValidationException::withMessages([
                'placements' => 'Pilih minimal satu unit dan jenjang.',
            ]);
        }

        return $normalized;
    }

    /**
     * @param  list<array{unit: string, jenjang: string}>  $placements
     */
    private function assertNameUnique(string $name, array $placements, ?int $ignoreId = null): void
    {
        foreach ($placements as $placement) {
            $exists = Subject::query()
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
                ->when($ignoreId !== null, fn ($query) => $query->whereKeyNot($ignoreId))
                ->whereHas('scopes', fn ($query) => $query
                    ->whereRaw('LOWER(unit) = ?', [$placement['unit']])
                    ->whereRaw('LOWER(jenjang) = ?', [mb_strtolower($placement['jenjang'])]))
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    'name' => "Mapel {$name} sudah ada untuk {$placement['unit']} - {$placement['jenjang']}.",
                ]);
            }
        }
    }
}
