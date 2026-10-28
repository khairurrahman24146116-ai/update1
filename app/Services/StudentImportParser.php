<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StudentImportParser
{
    /**
     * Header yang wajib ada. NISN wajib sebagai kolom, tetapi nilai kosong
     * diperlakukan sebagai peringatan pada level baris.
     *
     * @var list<string>
     */
    private const REQUIRED_HEADERS = ['nis', 'nisn', 'nama', 'jenis_kelamin', 'kelas'];

    /**
     * Alias header setelah dinormalisasi.
     *
     * @var array<string, list<string>>
     */
    private const HEADER_ALIASES = [
        'nis' => ['nis', 'nomor induk siswa', 'no induk siswa', 'n i s'],
        'nisn' => ['nisn', 'nomor induk siswa nasional', 'no induk siswa nasional'],
        'nama' => ['nama', 'nama siswa', 'nama santri', 'nama lengkap'],
        'jenis_kelamin' => ['jenis kelamin', 'gender', 'jk', 'l/p', 'kelamin', 'jenis kelamin l/p'],
        'tanggal_lahir' => ['tanggal lahir', 'tgl lahir', 'tanggal lahir siswa'],
        'kelas' => ['kelas', 'class', 'classroom', 'rombel', 'nama kelas'],
        'unit' => ['unit', 'kode unit', 'satuan pendidikan'],
        'asal_siswa' => ['asal siswa', 'asal', 'sumber', 'asal pendaftaran', 'jalur masuk'],
        'nama_ayah' => ['nama ayah', 'ayah', 'nama bapak', 'bapak', 'nama ayah kandung'],
        'nama_ibu' => ['nama ibu', 'ibu', 'nama ibu kandung'],
        'nama_wali' => ['nama wali', 'wali', 'nama wali murid'],
        'no_hp_wali' => [
            'no hp wali', 'no hp', 'nomor hp wali', 'nomor hp', 'hp wali',
            'wa wali', 'whatsapp wali', 'telepon wali', 'no telp wali',
            'no whatsapp wali', 'nomor wa wali',
        ],
    ];

    /**
     * @return array{
     *     rows: array<int, array{nomor_baris: int, data_mentah: array<string, string>, data: array<string, string>}>,
     *     headers: list<string>,
     *     unknown_headers: list<string>
     * }
     */
    public function parse(string $path, string $extension): array
    {
        $spreadsheet = $this->load($path, $extension);
        $sheet = $spreadsheet->getActiveSheet();

        [$headerRow, $headerCells] = $this->findHeaderRow($sheet);

        $aliasMap = $this->aliasMap();
        $mapped = [];
        $unknownHeaders = [];
        $detectedLabels = [];

        foreach ($headerCells as $column => $label) {
            $normalized = $this->normalizeHeader($label);
            $field = $aliasMap[$normalized] ?? null;

            if ($field === null) {
                $unknownHeaders[] = $label;

                continue;
            }

            if (isset($mapped[$field])) {
                throw ValidationException::withMessages([
                    'file' => ["Kolom '{$label}' terdeteksi lebih dari sekali. Gunakan satu kolom untuk setiap data."],
                ]);
            }

            $mapped[$field] = $column;
            $detectedLabels[] = $field;
        }

        $missing = array_values(array_diff(self::REQUIRED_HEADERS, array_keys($mapped)));
        if ($missing !== []) {
            $labels = [
                'nis' => 'NIS',
                'nisn' => 'NISN',
                'nama' => 'Nama',
                'jenis_kelamin' => 'Jenis Kelamin',
                'kelas' => 'Kelas',
            ];
            $names = array_map(fn (string $field) => $labels[$field] ?? $field, $missing);

            throw ValidationException::withMessages([
                'file' => [
                    'Kolom wajib tidak ditemukan: '.implode(', ', $names).'. '.
                    'Header yang terbaca: '.(implode(', ', $headerCells) ?: '-'),
                ],
            ]);
        }

        $rows = [];
        $highestRow = $sheet->getHighestDataRow();

        for ($row = $headerRow + 1; $row <= $highestRow; $row++) {
            $canonical = [];
            $original = [];
            $hasValue = false;

            foreach ($headerCells as $column => $label) {
                $value = $this->readCell($sheet, $column, $row);
                $original[$label] = $value;

                if ($value !== '') {
                    $hasValue = true;
                }
            }

            if (! $hasValue) {
                continue;
            }

            foreach ($mapped as $field => $column) {
                $canonical[$field] = $this->readCell(
                    $sheet,
                    $column,
                    $row,
                    $field === 'tanggal_lahir',
                );
            }

            $rows[] = [
                'nomor_baris' => $row,
                'data_mentah' => $original,
                'data' => $canonical,
            ];
        }

        $spreadsheet->disconnectWorksheets();

        return [
            'rows' => $rows,
            'headers' => $detectedLabels,
            'unknown_headers' => $unknownHeaders,
        ];
    }

    private function load(string $path, string $extension): Spreadsheet
    {
        try {
            if (in_array($extension, ['csv', 'txt'], true)) {
                $reader = new Csv;
                $reader->setInputEncoding($this->detectEncoding($path));
                $reader->setDelimiter($this->detectDelimiter($path));
                $reader->setSheetIndex(0);

                return $reader->load($path);
            }

            if ($extension === 'xlsx') {
                $reader = IOFactory::createReader('Xlsx');
                $reader->setReadDataOnly(true);

                return $reader->load($path);
            }
        } catch (\Throwable $e) {
            throw ValidationException::withMessages([
                'file' => ['File tidak dapat dibaca. Pastikan format .xlsx atau .csv dan tidak rusak.'],
            ]);
        }

        throw ValidationException::withMessages([
            'file' => ['Format file harus .xlsx atau .csv.'],
        ]);
    }

    /**
     * @return array{0: int, 1: array<int, string>}
     */
    private function findHeaderRow(Worksheet $sheet): array
    {
        $highestRow = min($sheet->getHighestDataRow(), 30);
        $highestColumn = Coordinate::columnIndexFromString($sheet->getHighestDataColumn());
        $aliasMap = $this->aliasMap();
        $firstNonEmpty = null;

        for ($row = 1; $row <= $highestRow; $row++) {
            $cells = [];
            $recognized = 0;

            for ($column = 1; $column <= $highestColumn; $column++) {
                $value = $this->readCell($sheet, $column, $row);

                if ($value === '') {
                    continue;
                }

                $cells[$column] = $value;

                if (isset($aliasMap[$this->normalizeHeader($value)])) {
                    $recognized++;
                }
            }

            if ($cells === []) {
                continue;
            }

            $firstNonEmpty ??= [$row, $cells];

            if ($recognized >= 3) {
                return [$row, $cells];
            }
        }

        if ($firstNonEmpty !== null) {
            return $firstNonEmpty;
        }

        throw ValidationException::withMessages([
            'file' => ['File tidak memiliki baris header yang bisa dibaca.'],
        ]);
    }

    private function readCell(Worksheet $sheet, int $column, int $row, bool $preferRawNumeric = false): string
    {
        $cell = $sheet->getCell([$column, $row]);
        $raw = $cell->getValue();

        if (is_string($raw) && $raw !== '' && in_array($raw[0], ['=', '+', '-', '@'], true)) {
            return trim($raw);
        }

        if ($preferRawNumeric && is_numeric($raw)) {
            return trim((string) $raw);
        }

        $formatted = $cell->getFormattedValue();
        $value = ($formatted !== '' && $formatted !== null) ? $formatted : $raw;

        return trim(str_replace("\xC2\xA0", ' ', (string) $value));
    }

    private function normalizeHeader(string $value): string
    {
        $value = str_replace("\xEF\xBB\xBF", '', $value);
        $value = preg_replace('/\([^)]*\)/', ' ', $value) ?? $value;
        $value = mb_strtolower(trim($value));
        $value = str_replace(['_', '-', '.'], ' ', $value);
        $value = preg_replace('/[^\p{L}\p{N}\/ ]+/u', ' ', $value) ?? $value;
        $value = preg_replace('/\s+/', ' ', $value) ?? $value;

        return trim($value);
    }

    /**
     * @return array<string, string>
     */
    private function aliasMap(): array
    {
        $map = [];

        foreach (self::HEADER_ALIASES as $field => $aliases) {
            foreach ($aliases as $alias) {
                $map[$this->normalizeHeader($alias)] = $field;
            }
        }

        return $map;
    }

    private function detectDelimiter(string $path): string
    {
        $handle = fopen($path, 'r');
        $line = $handle ? (string) fgets($handle) : '';
        if ($handle) {
            fclose($handle);
        }

        $counts = [
            ',' => substr_count($line, ','),
            ';' => substr_count($line, ';'),
            "\t" => substr_count($line, "\t"),
            '|' => substr_count($line, '|'),
        ];

        arsort($counts);
        $delimiter = array_key_first($counts);

        return $counts[$delimiter] > 0 ? $delimiter : ',';
    }

    private function detectEncoding(string $path): string
    {
        $handle = fopen($path, 'r');
        $sample = $handle ? (string) fgets($handle) : '';
        if ($handle) {
            fclose($handle);
        }

        return mb_check_encoding($sample, 'UTF-8') ? 'UTF-8' : 'CP1252';
    }
}
