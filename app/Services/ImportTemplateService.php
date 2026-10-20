<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Classroom;
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

        $sheet->getStyle('A1:K1')->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle('A1:K1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF1F4E79');

        $exampleClasses = $classes->pluck('name')->values();
        $examples = [
            ['2026001', '0071234567', 'Andi Putra', 'L', '2010-07-14', $exampleClasses->get(0) ?? 'X-1', 'internal', 'Ayah Andi', 'Ibu Andi', '', '081234567890'],
            ['2026002', '0071234568', 'Siti Putri', 'P', '2010-09-02', $exampleClasses->get(1) ?? 'X-2', 'ppdb', 'Ayah Siti', 'Ibu Siti', 'Wali Siti', '+6281234567891'],
        ];

        foreach ($examples as $offset => $example) {
            $row = $offset + 2;

            foreach ($example as $index => $value) {
                $column = Coordinate::stringFromColumnIndex($index + 1);
                $sheet->setCellValueExplicit($column.$row, (string) $value, DataType::TYPE_STRING);
            }
        }

        foreach (range('A', 'K') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $sheet->freezePane('A2');

        $lastClassRow = max(2, $classes->count() + 1);

        $this->listValidation($sheet, 'D2:D2000', '"L,P"');
        $this->listValidation($sheet, 'G2:G2000', '"internal,ppdb"');

        if ($classes->isNotEmpty()) {
            $this->listValidation($sheet, 'F2:F2000', '=Kelas!$A$2:$A$'.$lastClassRow);
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
        $sheet->setCellValue('B1', 'Jalur');
        $sheet->setCellValue('C1', 'Jenjang');
        $sheet->setCellValue('D1', 'Tingkat');
        $sheet->getStyle('A1:D1')->getFont()->setBold(true);

        $row = 2;

        foreach ($classes as $class) {
            $sheet->setCellValue('A'.$row, $class->name);
            $sheet->setCellValue('B'.$row, $class->jalur);
            $sheet->setCellValue('C'.$row, $class->jenjang);
            $sheet->setCellValue('D'.$row, $class->grade);
            $row++;
        }

        foreach (range('A', 'D') as $column) {
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

        $lines = [
            'Petunjuk Import Data Santri',
            '',
            'Tahun ajaran: '.$year->name,
            '',
            '1. Jangan mengubah atau menghapus baris header.',
            '2. Kolom wajib: NIS, NISN, Nama, Jenis Kelamin, Kelas.',
            '3. NISN wajib berupa kolom, tetapi boleh dikosongkan (akan menjadi peringatan).',
            '4. Jenis Kelamin: L atau P.',
            '5. Tanggal Lahir: YYYY-MM-DD atau DD/MM/YYYY (boleh kosong).',
            '6. Kelas harus persis salah satu nama pada sheet Kelas.',
            '7. Asal Siswa: internal (santri lama) atau ppdb (santri baru).',
            '8. No HP Wali: awali 08 atau +62. Nomor tidak valid disimpan kosong (peringatan).',
            '9. Sistem mengikuti jalur/jenjang dari master Kelas, bukan nama kelas.',
            '10. Mode update tidak mengubah kelas siswa yang sudah terdaftar.',
            '',
            'Kelas tersedia pada tahun ajaran ini: '.($classes->isEmpty() ? '(belum ada kelas)' : $classes->pluck('name')->implode(', ')),
        ];

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
