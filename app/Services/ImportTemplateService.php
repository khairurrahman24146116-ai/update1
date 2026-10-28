<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Unit;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ImportTemplateService
{
    /**
     * @var list<string>
     */
    private const HEADERS = [
        'NIS',
        'NISN',
        'Nama',
        'Jenis Kelamin',
        'Tanggal Lahir',
        'Kelas',
        'Unit',
        'Asal Siswa',
        'Nama Ayah',
        'Nama Ibu',
        'Nama Wali',
        'No HP Wali',
    ];

    public function path(AcademicYear $year): string
    {
        $spreadsheet = new Spreadsheet;
        $classes = $this->classes($year);

        $this->buildDataSheet($spreadsheet, $classes);
        $this->buildExampleSheet($spreadsheet, $classes);
        $this->buildClassSheet($spreadsheet, $classes);
        $this->buildGuideSheet($spreadsheet, $year, $classes);

        $spreadsheet->setActiveSheetIndex(0);

        $tmp = tempnam(sys_get_temp_dir(), 'template_siswa_');
        $path = $tmp.'.xlsx';
        @unlink($tmp);
        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();

        return $path;
    }

    /**
     * @param  Collection<int, Classroom>  $classes
     */
    private function buildDataSheet(Spreadsheet $spreadsheet, $classes): void
    {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Siswa');

        foreach (self::HEADERS as $index => $header) {
            $column = Coordinate::stringFromColumnIndex($index + 1);
            $sheet->setCellValue($column.'1', $header);
        }

        $sheet->getStyle('A1:L1')->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle('A1:L1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF1F4E79');

        foreach (range('A', 'L') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $sheet->freezePane('A2');

        $lastClassRow = max(2, $classes->count() + 1);
        $unitCodes = array_map('strtoupper', Unit::enabledCodes());

        $this->listValidation($sheet, 'D2:D2000', '"L,P"');
        $this->listValidation($sheet, 'H2:H2000', '"internal,ppdb"');

        if ($unitCodes !== []) {
            $this->listValidation($sheet, 'G2:G2000', '"'.implode(',', $unitCodes).'"');
        }

        if ($classes->isNotEmpty()) {
            $this->listValidation($sheet, 'F2:F2000', '=Kelas!$A$2:$A$'.$lastClassRow);
        }
    }

    /**
     * @param  Collection<int, Classroom>  $classes
     */
    private function buildExampleSheet(Spreadsheet $spreadsheet, $classes): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Contoh');

        $sheet->mergeCells('A1:L1');
        $sheet->setCellValue('A1', 'CONTOH SAJA - JANGAN UPLOAD SHEET INI. Salin formatnya ke sheet Data Siswa, lalu hapus data contoh.');
        $sheet->getStyle('A1')->getFont()->setBold(true)->getColor()->setARGB('FF9C0006');
        $sheet->getStyle('A1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFFC7CE');
        $sheet->getStyle('A1')->getAlignment()->setWrapText(true);

        foreach (self::HEADERS as $index => $header) {
            $column = Coordinate::stringFromColumnIndex($index + 1);
            $sheet->setCellValue($column.'3', $header);
        }

        $sheet->getStyle('A3:L3')->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle('A3:L3')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF1F4E79');

        $first = $classes->values()->get(0);
        $second = $classes->values()->get(1) ?? $first;
        $fallbackUnit = strtoupper((string) (Unit::enabledCodes()[0] ?? 'SMA'));

        $examples = [
            [
                '2026001', '0071234567', 'Andi Putra', 'L', '2010-07-14',
                $first?->name ?? 'X-1', strtoupper((string) ($first?->unit ?? $fallbackUnit)),
                'internal', 'Ayah Andi', 'Ibu Andi', '', '081234567890',
            ],
            [
                '2026002', '0071234568', 'Siti Putri', 'P', '2010-09-02',
                $second?->name ?? 'X-2', strtoupper((string) ($second?->unit ?? $fallbackUnit)),
                'ppdb', 'Ayah Siti', 'Ibu Siti', 'Wali Siti', '+6281234567891',
            ],
        ];

        foreach ($examples as $offset => $example) {
            $row = $offset + 4;

            foreach ($example as $index => $value) {
                $column = Coordinate::stringFromColumnIndex($index + 1);
                $sheet->setCellValueExplicit($column.$row, (string) $value, DataType::TYPE_STRING);
            }
        }

        foreach (range('A', 'L') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
    }

    /**
     * @param  Collection<int, Classroom>  $classes
     */
    private function buildClassSheet(Spreadsheet $spreadsheet, $classes): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Kelas');

        $sheet->setCellValue('A1', 'Nama Kelas');
        $sheet->setCellValue('B1', 'Unit');
        $sheet->setCellValue('C1', 'Jalur');
        $sheet->setCellValue('D1', 'Jenjang');
        $sheet->setCellValue('E1', 'Tingkat');
        $sheet->getStyle('A1:E1')->getFont()->setBold(true);

        $row = 2;

        foreach ($classes as $class) {
            $sheet->setCellValue('A'.$row, $class->name);
            $sheet->setCellValue('B'.$row, strtoupper((string) $class->unit));
            $sheet->setCellValue('C'.$row, $class->jalur);
            $sheet->setCellValue('D'.$row, $class->jenjang);
            $sheet->setCellValue('E'.$row, $class->grade);
            $row++;
        }

        foreach (range('A', 'E') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
    }

    /**
     * @param  Collection<int, Classroom>  $classes
     */
    private function buildGuideSheet(Spreadsheet $spreadsheet, AcademicYear $year, $classes): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Petunjuk');

        $classDetails = $classes
            ->map(fn (Classroom $class): string => '- '.$class->name
                .' | Unit: '.strtoupper((string) $class->unit)
                .' | Jalur: '.$class->jalur
                .' | Jenjang: '.$class->jenjang
                .' | Tingkat: '.$class->grade)
            ->values()
            ->all();

        $unitHint = collect(Unit::enabledCodes())->map(fn (string $unit): string => strtoupper($unit))->implode('/');

        $lines = array_merge([
            'Petunjuk Import Data Santri',
            '',
            'Tahun ajaran: '.$year->name,
            '',
            'ATURAN PENTING:',
            '1. Jika ada SATU baris error, SELURUH file ditolak. Tidak ada data yang disimpan.',
            '2. Sheet Data Siswa sengaja kosong. Contoh ada di sheet Contoh; jangan upload sheet Contoh.',
            '3. Jangan mengubah atau menghapus header. Urutan kolom boleh berbeda.',
            '4. Kolom wajib: NIS, NISN, Nama, Jenis Kelamin, Kelas.',
            '5. NISN boleh kosong (peringatan). Format NISN yang disarankan 10 digit.',
            '6. Jenis Kelamin: L atau P.',
            '7. Tanggal Lahir: YYYY-MM-DD atau DD/MM/YYYY (boleh kosong).',
            '8. Kelas harus persis salah satu nama pada sheet Kelas.',
            '9. Unit: '.($unitHint !== '' ? $unitHint : '-').'. Isi kolom Unit jika nama kelas sama di lebih dari satu unit.',
            '10. Asal Siswa: internal (santri lama) atau ppdb (santri baru).',
            '11. No HP Wali: awali 08 atau +62. Nomor tidak valid disimpan kosong (peringatan).',
            '12. Sistem mengikuti jalur/jenjang dari master Kelas, bukan nama kelas.',
            '13. Mode update tidak mengubah kelas siswa yang sudah terdaftar.',
            '14. Maksimal 2000 baris. NIS harus unik.',
            '',
            'Daftar kelas pada tahun ajaran ini:',
        ], $classDetails !== [] ? $classDetails : ['(belum ada kelas)']);

        $row = 1;

        foreach ($lines as $line) {
            $sheet->setCellValue('A'.$row, $line);
            $row++;
        }

        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getColumnDimension('A')->setWidth(110);
        $sheet->getStyle('A1:A'.count($lines))->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
    }

    private function listValidation(Worksheet $sheet, string $range, string $formula): void
    {
        $validation = $sheet->getCell(explode(':', $range)[0])->getDataValidation();
        $validation->setType(DataValidation::TYPE_LIST);
        $validation->setErrorStyle(DataValidation::STYLE_STOP);
        $validation->setAllowBlank(true);
        $validation->setShowDropDown(true);
        $validation->setShowErrorMessage(true);
        $validation->setErrorTitle('Input tidak valid');
        $validation->setError('Pilih nilai yang tersedia.');
        $validation->setFormula1($formula);
        $validation->setSqref($range);
    }

    /**
     * @return Collection<int, Classroom>
     */
    private function classes(AcademicYear $year)
    {
        return Classroom::query()
            ->where(function ($query) use ($year) {
                $query->where('tahun_ajaran_id', $year->id)
                    ->orWhere(function ($query) use ($year) {
                        $query->whereNull('tahun_ajaran_id')->where('academic_year', $year->name);
                    });
            })
            ->orderBy('grade')
            ->orderBy('name')
            ->get();
    }
}
