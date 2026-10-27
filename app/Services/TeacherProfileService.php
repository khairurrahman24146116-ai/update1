<?php

namespace App\Services;

use App\Models\Scopes\BerdasarkanUnit;
use App\Models\Teacher;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class TeacherProfileService
{
    public function ensureForUser(User $user): Teacher
    {
        $teacher = Teacher::withoutGlobalScope(BerdasarkanUnit::class)
            ->firstOrCreate(
                ['user_id' => $user->id],
                ['nama' => $user->name],
            );

        if ($teacher->nama !== $user->name) {
            $teacher->update(['nama' => $user->name]);
        }

        return $teacher;
    }

    public function syncFromUser(User $user): Teacher
    {
        $teacher = $this->ensureForUser($user);
        $units = $this->unitPayloadFromUser($user);

        $teacher->units()->sync($units);

        return $teacher->load('units');
    }

    /**
     * @param  array<int, array{code: string, jabatan?: string|null}>  $units
     */
    public function updateUnits(Teacher $teacher, array $units): Teacher
    {
        $user = $teacher->user;

        if ($user === null) {
            throw ValidationException::withMessages([
                'units' => 'Profil guru belum terhubung dengan akun pengguna.',
            ]);
        }

        $payload = [];
        $codes = [];

        foreach ($units as $item) {
            $code = strtoupper(trim((string) ($item['code'] ?? '')));
            $unit = Unit::query()->whereRaw('UPPER(kode) = ?', [$code])->first();

            if (! $unit) {
                throw ValidationException::withMessages([
                    'units' => "Unit {$code} tidak ditemukan.",
                ]);
            }

            if (array_key_exists($unit->id, $payload)) {
                continue;
            }

            $payload[$unit->id] = ['jabatan' => $item['jabatan'] ?? null];
            $codes[] = strtolower($unit->kode);
        }

        $teacher->units()->sync($payload);
        $user->units()->sync($payload);
        $user->update(['unit' => $codes[0] ?? null]);

        return $teacher->fresh(['units']);
    }

    /**
     * @return array<int, array{jabatan: string|null}>
     */
    private function unitPayloadFromUser(User $user): array
    {
        return $user->units()
            ->get()
            ->mapWithKeys(fn (Unit $unit): array => [
                $unit->id => ['jabatan' => $unit->pivot->jabatan],
            ])
            ->all();
    }
}
