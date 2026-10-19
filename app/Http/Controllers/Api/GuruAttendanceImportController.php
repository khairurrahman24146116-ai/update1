<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Schedule;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;

class GuruAttendanceImportController extends Controller
{
    private const MAX_FILE_KB = 5120;

    private const MAX_ROWS = 1000;

    private const ALLOWED_STATUS = ['hadir', 'izin', 'sakit', 'alfa'];

    public function preview(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|mimetypes:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel|max:'.self::MAX_FILE_KB,
            'schedule_id' => 'required|exists:schedules,id',
            'date' => 'required|date_format:Y-m-d',
        ]);
        $schedule = Schedule::findOrFail($request->schedule_id);
        $this->assertAuthorized($request, $schedule);
        $rows = $this->readRows($request->file('file')->getRealPath());
        $this->assertRowLimit($rows);
        $result = $this->validateRows($rows, $schedule, $request->date);

        return response()->json(array_merge($result, ['schedule_id' => $schedule->id, 'date' => $request->date]));
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|mimetypes:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel|max:'.self::MAX_FILE_KB,
            'schedule_id' => 'required|exists:schedules,id',
            'date' => 'required|date_format:Y-m-d',
        ]);
        $schedule = Schedule::findOrFail($request->schedule_id);
        $this->assertAuthorized($request, $schedule);
        $rows = $this->readRows($request->file('file')->getRealPath());
        $this->assertRowLimit($rows);
        $result = $this->validateRows($rows, $schedule, $request->date);
        $imported = 0;
        $skipped = 0;
        $failed = 0;
        $errors = [];
        DB::transaction(function () use ($result, $schedule, $request, &$imported, &$skipped, &$failed, &$errors) {
            foreach ($result['rows'] as $r) {
                if ($r['status'] !== 'PASS') {
                    $failed++;

                    continue;
                }
                $sid = $r['student_id'];
                $exists = Attendance::where('schedule_id', $schedule->id)->where('student_id', $sid)->where('date', $request->date)->exists();
                if ($exists) {
                    $skipped++;

                    continue;
                }
                Attendance::create(['schedule_id' => $schedule->id, 'student_id' => $sid, 'date' => $request->date, 'status' => $r['data']['status'], 'notes' => $r['data']['notes'] ?? null]);
                $imported++;
            }
        });

        return response()->json(['imported' => $imported, 'skipped' => $skipped, 'failed' => $failed, 'errors' => $errors, 'preview' => $result]);
    }

    private function assertAuthorized(Request $request, Schedule $schedule): void
    {
        $user = $request->user();
        if ($user->isAdmin()) {
            return;
        }
        if ($user->role === 'guru') {
            if ((int) $schedule->teacher_id === (int) $user->id) {
                return;
            }
            abort(403, 'Guru hanya dapat mengimpor absensi jadwal miliknya.');
        }
        if ($user->hasPermission('attendance.import')) {
            return;
        }
        abort(403);
    }

    private function assertRowLimit(array $rows): void
    {
        if (count($rows) > self::MAX_ROWS) {
            throw ValidationException::withMessages(['file' => ['Jumlah baris melebihi batas '.self::MAX_ROWS.' baris. File berisi '.count($rows).' baris.']]);
        }
    }

    private function readRows(string $path): array
    {
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $sheet = $reader->load($path)->getActiveSheet();
        $data = $sheet->toArray(null, true, true, true);
        if (empty($data)) {
            return [];
        }
        $headerRow = $data[1] ?? [];
        $norm = fn ($v) => strtolower(trim((string) $v));
        $map = [];
        $hasHeader = false;
        foreach ($headerRow as $col => $val) {
            $n = $norm($val);
            if (in_array($n, ['nis', 'nisn', 'nama', 'name', 'status', 'keterangan', 'notes', 'catatan'])) {
                $hasHeader = true;
            }
            if ($n === 'nis') {
                $map['nis'] = $col;
            } elseif ($n === 'nisn') {
                $map['nisn'] = $col;
            } elseif (in_array($n, ['status'])) {
                $map['status'] = $col;
            } elseif (in_array($n, ['keterangan', 'notes', 'catatan'])) {
                $map['notes'] = $col;
            }
        }
        $rows = [];
        foreach ($data as $idx => $row) {
            if ($idx === 1 && $hasHeader) {
                continue;
            }
            if (empty(array_filter($row, fn ($v) => trim((string) $v) !== ''))) {
                continue;
            }
            if ($hasHeader && count($map) >= 1) {
                $rows[] = [
                    'row' => $idx,
                    'nis' => trim((string) ($row[$map['nis'] ?? 'A'] ?? '')),
                    'nisn' => trim((string) ($row[$map['nisn'] ?? 'B'] ?? '')),
                    'status' => strtolower(trim((string) ($row[$map['status'] ?? 'C'] ?? ''))),
                    'notes' => trim((string) ($row[$map['notes'] ?? 'D'] ?? '')),
                ];
            } else {
                $rows[] = ['row' => $idx, 'nis' => trim($row['A'] ?? ''), 'nisn' => trim($row['B'] ?? ''), 'status' => strtolower(trim($row['C'] ?? '')), 'notes' => trim($row['D'] ?? '')];
            }
        }
        if (! $hasHeader && isset($data[1])) {
            $r1 = $data[1];
            if (! empty(array_filter($r1, fn ($v) => trim((string) $v) !== ''))) {
                $alreadyHasRow1 = ! empty($rows) && $rows[0]['row'] === 1;
                if (! $alreadyHasRow1) {
                    array_unshift($rows, ['row' => 1, 'nis' => trim($r1['A'] ?? ''), 'nisn' => trim($r1['B'] ?? ''), 'status' => strtolower(trim($r1['C'] ?? '')), 'notes' => trim($r1['D'] ?? '')]);
                }
            }
        }

        return $rows;
    }

    private function validateRows(array $rows, Schedule $schedule, string $date): array
    {
        $valid = 0;
        $invalid = 0;
        $out = [];
        $seen = [];
        $nisList = array_values(array_filter(array_map(fn ($r) => $r['nis'] ?? '', $rows)));
        $nisnList = array_values(array_filter(array_map(fn ($r) => $r['nisn'] ?? '', $rows)));
        $studentsByNis = $nisList ? Student::whereIn('nis', $nisList)->get()->keyBy(fn ($s) => (string) $s->nis) : collect();
        $studentsByNisn = $nisnList ? Student::whereIn('nisn', $nisnList)->get()->keyBy(fn ($s) => (string) $s->nisn) : collect();
        $existingKeys = Attendance::where('schedule_id', $schedule->id)->whereDate('date', $date)->pluck('student_id')->flip()->toArray();
        foreach ($rows as $r) {
            $errs = [];
            $idVal = $r['nis'] ?: $r['nisn'];
            $lookup = $r['nis'] ? ($studentsByNis[(string) $r['nis']] ?? null) : ($studentsByNisn[(string) $r['nisn']] ?? null);
            if (empty($r['nis']) && empty($r['nisn'])) {
                $errs[] = 'NIS/NISN kosong';
            }
            if (empty($r['status'])) {
                $errs[] = 'Status kosong';
            } elseif (! in_array($r['status'], self::ALLOWED_STATUS)) {
                $errs[] = 'Status tidak valid (hadir/izin/sakit/alfa)';
            }
            if ($idVal !== '' && isset($seen[$idVal])) {
                $errs[] = 'Duplicate dalam file';
            }
            if (! empty($idVal) && ! $lookup) {
                $errs[] = 'Siswa tidak ditemukan';
            }
            if ($lookup && isset($existingKeys[$lookup->id])) {
                $errs[] = 'Absensi sudah ada untuk tanggal ini';
            }
            if ($idVal !== '') {
                $seen[$idVal] = true;
            }
            $status = empty($errs) ? 'PASS' : 'ERROR';
            if ($status === 'PASS') {
                $valid++;
            } else {
                $invalid++;
            }
            $out[] = ['row' => $r['row'], 'data' => $r, 'student_id' => $lookup?->id, 'status' => $status, 'errors' => $errs];
        }

        return ['total' => count($rows), 'valid' => $valid, 'invalid' => $invalid, 'rows' => $out];
    }
}
