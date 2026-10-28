<?php

namespace App\Support;

use App\Models\Classroom;
use Carbon\CarbonImmutable;
use DateTimeInterface;

class WelfarePeriod
{
    public static function academicYearFor(?Classroom $classroom): string
    {
        $year = trim((string) $classroom?->academic_year);

        if (preg_match('/^\d{4}\/\d{4}$/', $year) === 1) {
            return $year;
        }

        $now = CarbonImmutable::now();

        return $now->month >= 7
            ? $now->year.'/'.($now->year + 1)
            : ($now->year - 1).'/'.$now->year;
    }

    public static function semesterFor(DateTimeInterface|string $date): string
    {
        return CarbonImmutable::parse($date)->month >= 7 ? 'ganjil' : 'genap';
    }

    /**
     * @return array{0: string, 1: string}
     */
    public static function academicYearRange(string $academicYear): array
    {
        if (preg_match('/^(\d{4})\/(\d{4})$/', $academicYear, $matches) === 1) {
            return [$matches[1].'-07-01', $matches[2].'-06-30'];
        }

        $now = CarbonImmutable::now();
        $startYear = $now->month >= 7 ? $now->year : $now->year - 1;

        return [$startYear.'-07-01', ($startYear + 1).'-06-30'];
    }

    /**
     * @return array{0: string, 1: string}
     */
    public static function semesterRange(string $academicYear, string $semester): array
    {
        [$start, $end] = self::academicYearRange($academicYear);

        if ($semester === 'genap') {
            return [CarbonImmutable::parse($end)->startOfYear()->toDateString(), $end];
        }

        return [$start, CarbonImmutable::parse($start)->endOfYear()->toDateString()];
    }

    /**
     * @return array{0: string, 1: string}
     */
    public static function monthRange(DateTimeInterface|string $date): array
    {
        $month = CarbonImmutable::parse($date);

        return [$month->startOfMonth()->toDateString(), $month->endOfMonth()->toDateString()];
    }
}
