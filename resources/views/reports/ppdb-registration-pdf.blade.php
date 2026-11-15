<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Formulir PPDB {{ $registration->registration_number }}</title>
    <style>
        @page { margin: 18px 26px 26px; }
        * { box-sizing: border-box; }
        body { font-family: "DejaVu Sans", sans-serif; font-size: 9px; color: #111827; line-height: 1.35; }
        .kop-surat { margin-bottom: 6px; }
        .kop-surat img { display: block; }

        .doc-head { width: 100%; border-collapse: collapse; margin: 8px 0 6px; }
        .doc-head td { vertical-align: top; }
        .doc-title { text-align: center; }
        .doc-title .l1 { font-size: 12.5px; font-weight: bold; letter-spacing: .2px; }
        .doc-title .l2 { font-size: 12.5px; font-weight: bold; }
        .doc-title .l3 { font-size: 11.5px; font-weight: bold; }
        .doc-title .form-title { font-size: 14px; font-weight: bold; letter-spacing: .6px; margin-top: 5px; }

        .photo-cell { width: 78px; }
        .photo-box {
            width: 76px; height: 100px; border: 1px solid #374151;
            display: block; text-align: center;
            font-size: 8px; color: #4b5563; padding: 34px 2px 0;
        }
        .photo-box img { width: 72px; height: 96px; object-fit: cover; }
        .photo-caption { width: 76px; text-align: center; font-size: 7.5px; margin-top: 2px; }

        .guide { font-size: 8px; margin: 6px 0 8px; }
        .guide ol { margin: 2px 0 0 16px; padding: 0; }
        .guide li { margin: 0; }

        .section { margin-top: 8px; }
        .section-title {
            background: #e8f3ee; border-left: 4px solid #16803c;
            color: #14532d; font-weight: bold; font-size: 9.5px;
            padding: 3px 7px; text-transform: uppercase; letter-spacing: .3px;
        }
        table.fields { width: 100%; border-collapse: collapse; margin-top: 3px; }
        table.fields td { padding: 2.5px 4px; vertical-align: top; }
        table.fields td.no { width: 5%; color: #4b5563; }
        table.fields td.lbl { width: 31%; }
        table.fields td.val { width: 64%; border-bottom: 1px dotted #6b7280; font-weight: bold; }
        table.fields tr.alt td.val { border-bottom: 1px dotted #6b7280; }

        .subnote { font-size: 7.5px; color: #6b7280; margin: 2px 0 0 4px; }

        .page-break { page-break-after: always; }
        .running-head {
            border-bottom: 1px solid #16803c; padding-bottom: 3px; margin-bottom: 8px;
            font-size: 8px; color: #374151;
        }
        .running-head strong { color: #14532d; }
        .sheet { float: right; font-size: 8px; color: #6b7280; }

        .signatures { width: 100%; margin-top: 14px; border-collapse: collapse; }
        .signatures td { text-align: center; font-size: 8.5px; vertical-align: top; padding-top: 3px; }
        .signatures .space { height: 40px; }
        .signatures .name { border-top: 1px dotted #374151; padding-top: 3px; }
        .place-date { text-align: right; font-size: 8.5px; margin: 10px 8px 0 0; }
        .status-badge { display: inline-block; border: 1px solid #16803c; color: #14532d; padding: 1px 6px; font-size: 8px; border-radius: 8px; }
    </style>
</head>
<body>
    @php
        $months = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        $idDate = function ($value) use ($months) {
            if (! $value) {
                return '—';
            }
            $date = $value instanceof \Illuminate\Support\Carbon ? $value : \Illuminate\Support\Carbon::parse($value);
            return $date->format('d').' '.$months[(int) $date->format('n')].' '.$date->format('Y');
        };
        $v = fn ($value) => filled($value) ? e($value) : '—';
        $row = fn ($no, $label, $value) => '<tr><td class="no">'.e($no).'.</td><td class="lbl">'.e($label).'</td><td class="val">'.$v($value).'</td></tr>';
        $address = fn ($prefix) => collect([
            $registration->{$prefix.'address'} ?? null,
            $registration->{$prefix.'village'} ?? null,
            $registration->{$prefix.'district'} ?? null,
            $registration->{$prefix.'city'} ?? null,
            $registration->{$prefix.'province'} ?? null,
            $registration->{$prefix.'postal_code'} ?? null,
        ])->filter()->implode(', ');
        $nationality = fn ($value, $other = null) => $value === 'Lainnya'
            ? 'Lainnya: '.($other ?: '—')
            : ($value ?: '—');
        $gender = $registration->gender === 'L' ? 'Laki-Laki' : ($registration->gender === 'P' ? 'Perempuan' : '—');
        $jenjang = $registration->jenjangLabel();
    @endphp

    @include('reports.partials.kop-surat')

    <table class="doc-head">
        <tr>
            <td>
                <div class="doc-title">
                    <div class="l1">PANITIA PENERIMAAN SANTRI BARU</div>
                    <div class="l2">YAYASAN DAYAH MADANI AL-AZIZIYAH</div>
                    <div class="l3">TAHUN PELAJARAN 2026-2027</div>
                    <div class="form-title">FORMULIR PENDAFTARAN</div>
                </div>
            </td>
            <td class="photo-cell">
                <div class="photo-box">
                    @if ($photoPath && is_file($photoPath))
                        <img src="{{ 'data:image/'.(pathinfo($photoPath, PATHINFO_EXTENSION) === 'png' ? 'png' : 'jpeg').';base64,'.base64_encode((string) file_get_contents($photoPath)) }}" alt="Pas Foto">
                    @else
                        Pas Photo<br>3x4<br>Latar Biru
                    @endif
                </div>
                <div class="photo-caption">Pas Photo 3x4<br>Latar Belakang Biru</div>
            </td>
        </tr>
    </table>

    <div class="guide">
        <strong>Petunjuk Pengisian:</strong>
        <ol>
            <li>Diisi oleh orang tua/wali santri.</li>
            <li>Penulisan menggunakan huruf KAPITAL.</li>
            <li>*) Coret yang tidak perlu. **) Diisi apabila status Yatim. ***) Diisi khusus Putri.</li>
        </ol>
    </div>

    <div class="section">
        <div class="section-title">I. Registrasi</div>
        <table class="fields">
            {!! $row(1, 'Nomor Pendaftaran', $registration->registration_number) !!}
            {!! $row(2, 'Tanggal Pendaftaran', $idDate($registration->created_at)) !!}
            {!! $row(3, 'Jenjang / Jalur Pendaftaran', $jenjang) !!}
            {!! $row(4, 'Gelombang', $registration->wave?->name) !!}
        </table>
    </div>

    <div class="section">
        <div class="section-title">II. Identitas Santri</div>
        <table class="fields">
            {!! $row(5, 'Nama Lengkap', $registration->name) !!}
            {!! $row(6, 'NIK', $registration->nik) !!}
            {!! $row(7, 'Jenis Kelamin', $gender) !!}
            {!! $row(8, 'Tempat Lahir', $registration->birth_place) !!}
            {!! $row(9, 'Tanggal Lahir', $idDate($registration->birth_date)) !!}
            {!! $row(10, 'Kewarganegaraan', $nationality($registration->nationality, $registration->nationality_other)) !!}
            {!! $row(11, 'Anak Ke', $registration->child_order) !!}
            {!! $row(12, 'Jumlah Saudara', $registration->sibling_count) !!}
            {!! $row(13, 'Status Anak', $registration->orphan_status) !!}
            {!! $row(14, 'Bahasa Sehari-hari', $registration->daily_language === 'Lainnya' ? 'Lainnya: '.($registration->daily_language_other ?: '—') : $registration->daily_language) !!}
            {!! $row(15, 'Alamat Lengkap', $registration->address) !!}
            {!! $row(16, 'Desa / Gampong', $registration->village) !!}
            {!! $row(17, 'Kecamatan', $registration->district) !!}
            {!! $row(18, 'Kabupaten / Kota', $registration->city) !!}
            {!! $row(19, 'Provinsi', $registration->province) !!}
            {!! $row(20, 'Kode Pos', $registration->postal_code) !!}
            {!! $row(21, 'No. Telepon / HP', $registration->phone) !!}
            {!! $row(22, 'Email', $registration->email) !!}
        </table>
    </div>

    <div class="section">
        <div class="section-title">III. Identitas Sekolah Asal</div>
        <table class="fields">
            {!! $row(23, 'Nama Sekolah (SD/MI atau SMP/MTs)', $registration->origin_school_name) !!}
            {!! $row(24, 'Alamat Sekolah Asal', $registration->origin_school_address) !!}
            {!! $row(25, 'NISN', $registration->nisn) !!}
        </table>
    </div>

    <div class="page-break"></div>

    <div class="running-head">
        Formulir Pendaftaran Santri Baru &mdash; <strong>{{ $registration->registration_number ?: '#'.$registration->id }}</strong> &mdash; {{ $registration->name }}
        <span class="sheet">Lembaran 2</span>
    </div>

    <div class="section" style="margin-top:0;">
        <div class="section-title">IV. Identitas Ayah Kandung</div>
        <table class="fields">
            {!! $row(26, 'Nomor Kartu Keluarga', $registration->father_family_card) !!}
            {!! $row(27, 'Nama Ayah Kandung', $registration->father_name) !!}
            {!! $row(28, 'NIK Ayah', $registration->father_nik) !!}
            {!! $row(29, 'Tempat Lahir', $registration->father_birth_place) !!}
            {!! $row(30, 'Tanggal Lahir', $idDate($registration->father_birth_date)) !!}
            {!! $row(31, 'Kewarganegaraan', $nationality($registration->father_nationality, $registration->father_nationality_other)) !!}
            {!! $row(32, 'Pendidikan Terakhir', $registration->father_education) !!}
            {!! $row(33, 'Pekerjaan', $registration->father_occupation) !!}
            {!! $row(34, 'Nomor Telepon / HP', $registration->father_phone) !!}
            {!! $row(35, 'Alamat', $address('father_')) !!}
            @if ($registration->father_is_deceased)
                {!! $row(36, 'Meninggal Dunia (Tanggal)', $idDate($registration->father_death_date)) !!}
                {!! $row(37, 'Tempat Meninggal', $registration->father_death_place) !!}
                {!! $row(38, 'Sebab Meninggal', $registration->father_death_cause) !!}
            @endif
        </table>
    </div>

    <div class="section">
        <div class="section-title">V. Identitas Ibu Kandung</div>
        <table class="fields">
            {!! $row(39, 'Nomor Kartu Keluarga', $registration->mother_family_card) !!}
            {!! $row(40, 'Nama Ibu Kandung', $registration->mother_name) !!}
            {!! $row(41, 'NIK Ibu', $registration->mother_nik) !!}
            {!! $row(42, 'Tempat Lahir', $registration->mother_birth_place) !!}
            {!! $row(43, 'Tanggal Lahir', $idDate($registration->mother_birth_date)) !!}
            {!! $row(44, 'Kewarganegaraan', $nationality($registration->mother_nationality, $registration->mother_nationality_other)) !!}
            {!! $row(45, 'Pendidikan Terakhir', $registration->mother_education) !!}
            {!! $row(46, 'Pekerjaan', $registration->mother_occupation) !!}
            {!! $row(47, 'Nomor Telepon / HP', $registration->mother_phone) !!}
            {!! $row(48, 'Alamat', $address('mother_')) !!}
            @if ($registration->mother_is_deceased)
                {!! $row(49, 'Meninggal Dunia (Tanggal)', $idDate($registration->mother_death_date)) !!}
                {!! $row(50, 'Tempat Meninggal', $registration->mother_death_place) !!}
                {!! $row(51, 'Sebab Meninggal', $registration->mother_death_cause) !!}
            @endif
        </table>
    </div>

    <div class="section">
        <div class="section-title">VI. Identitas Yang Membiayai Santri</div>
        <table class="fields">
            {!! $row(52, 'Nama', $registration->financier_name) !!}
            {!! $row(53, 'Hubungan Dengan Santri', $registration->financier_relation) !!}
            {!! $row(54, 'Tempat Lahir', $registration->financier_birth_place) !!}
            {!! $row(55, 'Tanggal Lahir', $idDate($registration->financier_birth_date)) !!}
            {!! $row(56, 'Kewarganegaraan', $nationality($registration->financier_nationality, $registration->financier_nationality_other)) !!}
            {!! $row(57, 'Pendidikan Terakhir', $registration->financier_education) !!}
            {!! $row(58, 'Pekerjaan', $registration->financier_occupation) !!}
            {!! $row(59, 'Nomor Telepon / HP', $registration->financier_phone) !!}
            {!! $row(60, 'Alamat', $address('financier_')) !!}
        </table>
    </div>

    <div class="page-break"></div>

    <div class="running-head">
        Formulir Pendaftaran Santri Baru &mdash; <strong>{{ $registration->registration_number ?: '#'.$registration->id }}</strong> &mdash; {{ $registration->name }}
        <span class="sheet">Lembaran 3</span>
    </div>

    <div class="section" style="margin-top:0;">
        <div class="section-title">VII. Identitas Keluarga Terdekat Yang Mudah Dihubungi</div>
        <table class="fields">
            {!! $row(61, 'Nama', $registration->emergency_name) !!}
            {!! $row(62, 'Hubungan Dengan Santri', $registration->emergency_relation) !!}
            {!! $row(63, 'Tempat Lahir', $registration->emergency_birth_place) !!}
            {!! $row(64, 'Tanggal Lahir', $idDate($registration->emergency_birth_date)) !!}
            {!! $row(65, 'Kewarganegaraan', $nationality($registration->emergency_nationality, $registration->emergency_nationality_other)) !!}
            {!! $row(66, 'Pendidikan Terakhir', $registration->emergency_education) !!}
            {!! $row(67, 'Pekerjaan', $registration->emergency_occupation) !!}
            {!! $row(68, 'Nomor Telepon / HP', $registration->emergency_phone) !!}
            {!! $row(69, 'Alamat', $address('emergency_')) !!}
        </table>
    </div>

    <div class="section">
        <div class="section-title">VIII. Identitas Wali **)</div>
        <table class="fields">
            {!! $row(70, 'Nama Wali', $registration->guardian_not_required ? 'Tidak ada wali / tidak diperlukan' : $registration->guardian_name) !!}
            @if (! $registration->guardian_not_required)
                {!! $row(71, 'Hubungan Dengan Santri', $registration->guardian_relation) !!}
                {!! $row(72, 'Tempat Lahir', $registration->guardian_birth_place) !!}
                {!! $row(73, 'Tanggal Lahir', $idDate($registration->guardian_birth_date)) !!}
                {!! $row(74, 'Kewarganegaraan', $nationality($registration->guardian_nationality, $registration->guardian_nationality_other)) !!}
                {!! $row(75, 'Pendidikan Terakhir', $registration->guardian_education) !!}
                {!! $row(76, 'Pekerjaan', $registration->guardian_occupation) !!}
                {!! $row(77, 'Nomor Telepon / HP', $registration->guardian_phone) !!}
                {!! $row(78, 'Alamat', $address('guardian_')) !!}
            @endif
        </table>
    </div>

    <div class="section">
        <div class="section-title">IX. Identitas Mahram Santri Putri ***)</div>
        <table class="fields">
            {!! $row(79, 'Nama Mahram', $registration->mahram_name) !!}
            {!! $row(80, 'Hubungan Dengan Santri', $registration->mahram_relation) !!}
            {!! $row(81, 'Tempat Lahir', $registration->mahram_birth_place) !!}
            {!! $row(82, 'Tanggal Lahir', $idDate($registration->mahram_birth_date)) !!}
            {!! $row(83, 'Kewarganegaraan', $nationality($registration->mahram_nationality, $registration->mahram_nationality_other)) !!}
            {!! $row(84, 'Pendidikan Terakhir', $registration->mahram_education) !!}
            {!! $row(85, 'Pekerjaan', $registration->mahram_occupation) !!}
            {!! $row(86, 'Nomor Telepon / HP', $registration->mahram_phone) !!}
            {!! $row(87, 'Alamat', $address('mahram_')) !!}
        </table>
    </div>

    <div class="section">
        <div class="section-title">X. Riwayat Kesehatan Santri</div>
        <table class="fields">
            {!! $row(88, 'Golongan Darah', $registration->blood_type) !!}
            {!! $row(89, 'Pernah / Sedang Menderita Penyakit', collect($registration->medical_history ?? [])->filter()->implode(', ')) !!}
            {!! $row(90, 'Penyakit Lainnya', $registration->medical_history_other) !!}
            {!! $row(91, 'Keterangan Kesehatan', $registration->medical_history_note) !!}
        </table>
    </div>

    <div class="section">
        <div class="section-title">Pernyataan</div>
        <p style="margin:5px 4px 0; text-align:justify;">
            Saya yang bertanda tangan di bawah ini menyatakan bahwa data yang tercantum dalam formulir ini adalah benar dan dapat dipertanggungjawabkan.
            Apabila terdapat kekeliruan, saya bersedia memperbaiki sesuai ketentuan panitia penerimaan santri baru.
        </p>
    </div>

    <div class="place-date">Aceh Besar, {{ $idDate($registration->declaration_date ?? $registration->created_at) }}</div>

    <table class="signatures">
        <tr>
            <td style="width:33%;">Orang Tua / Wali</td>
            <td style="width:34%;">Santri</td>
            <td style="width:33%;">Panitia</td>
        </tr>
        <tr>
            <td class="space"></td>
            <td class="space"></td>
            <td class="space"></td>
        </tr>
        <tr>
            <td class="name">{{ $registration->declarant_name ?: '..............................' }}</td>
            <td class="name">{{ $registration->name ?: '..............................' }}</td>
            <td class="name">..............................</td>
        </tr>
    </table>

    <p style="margin-top:8px; font-size:7.5px; color:#6b7280; text-align:center;">
        Dicetak pada {{ $printedAt->format('d/m/Y H:i') }} WIB oleh {{ $printedBy?->name ?? 'Sistem' }}.
        Status pendaftaran: <span class="status-badge">{{ ucfirst($registration->status) }}</span>
    </p>
</body>
</html>
