<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Rekap Pendaftar PPDB</title>
    <style>
        @page { margin: 18px 22px 26px; }
        * { box-sizing: border-box; }
        body { font-family: "DejaVu Sans", sans-serif; font-size: 8px; color: #111827; line-height: 1.3; }
        .kop-surat { margin-bottom: 6px; }
        .kop-surat img { display: block; }
        h1 { font-size: 13px; text-align: center; margin: 8px 0 1px; letter-spacing: .3px; }
        h2 { font-size: 10px; text-align: center; margin: 0 0 8px; font-weight: normal; color: #374151; }
        .meta {
            border: 1px solid #cbd5e1; background: #f8fafc; padding: 5px 8px; margin-bottom: 7px;
            font-size: 7.5px; color: #374151;
        }
        .meta span { display: inline-block; margin-right: 14px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #94a3b8; padding: 3px 4px; vertical-align: top; }
        th { background: #e8f3ee; color: #14532d; font-size: 7.5px; text-transform: uppercase; text-align: left; }
        tr.even td { background: #f8fafc; }
        .center { text-align: center; }
        .muted { color: #64748b; }
        .badge { display: inline-block; padding: 0 5px; border-radius: 6px; border: 1px solid #94a3b8; font-size: 7px; }
        .footer { margin-top: 8px; font-size: 7px; color: #64748b; }
        .footer .left { float: left; }
        .footer .right { float: right; }
    </style>
</head>
<body>
    @php
        $jenjangLabels = \App\Models\PPDBRegistration::JENJANG_LABELS;
        $statusLabels = ['pending' => 'Menunggu', 'diterima' => 'Diterima', 'ditolak' => 'Ditolak', 'cadangan' => 'Cadangan'];
    @endphp

    @include('reports.partials.kop-surat', ['kopWidthPx' => 1070])

    <h1>REKAPITULASI PENDAFTAR SANTRI BARU</h1>
    <h2>Yayasan Dayah Madani Al-Aziziyah &mdash; Tahun Pelajaran 2026-2027</h2>

    <div class="meta">
        <span><strong>Gelombang:</strong> {{ $waveName ?: 'Semua gelombang' }}</span>
        <span><strong>Jalur:</strong> {{ $jenjangLabels[$filters['jenjang'] ?? ''] ?? 'Semua jalur' }}</span>
        <span><strong>Status:</strong> {{ $statusLabels[$filters['status'] ?? ''] ?? 'Semua status' }}</span>
        @if (! empty($filters['search']))
            <span><strong>Pencarian:</strong> {{ $filters['search'] }}</span>
        @endif
        @if (! empty($filters['date_from']) || ! empty($filters['date_to']))
            <span><strong>Tanggal:</strong>
                {{ ! empty($filters['date_from']) ? \Carbon\Carbon::parse($filters['date_from'])->format('d/m/Y') : '...' }}
                &ndash;
                {{ ! empty($filters['date_to']) ? \Carbon\Carbon::parse($filters['date_to'])->format('d/m/Y') : '...' }}
            </span>
        @endif
        <span><strong>Jumlah:</strong> {{ $registrations->count() }} pendaftar</span>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width:4%;">No</th>
                <th style="width:12%;">No. Pendaftaran</th>
                <th style="width:15%;">Nama Santri</th>
                <th style="width:11%;">Jalur</th>
                <th style="width:5%;">JK</th>
                <th style="width:12%;">NIK / NISN</th>
                <th style="width:14%;">Sekolah Asal</th>
                <th style="width:10%;">Gelombang</th>
                <th style="width:8%;">Status</th>
                <th style="width:9%;">Tgl Daftar</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($registrations as $index => $registration)
                <tr class="{{ $index % 2 === 1 ? 'even' : '' }}">
                    <td class="center">{{ $index + 1 }}</td>
                    <td>{{ $registration->registration_number ?: '#'.$registration->id }}</td>
                    <td>{{ $registration->name }}</td>
                    <td>{{ $jenjangLabels[$registration->jenjang] ?? '—' }}</td>
                    <td class="center">{{ $registration->gender ?: '—' }}</td>
                    <td>{{ $registration->nik ?: '—' }} / {{ $registration->nisn ?: '—' }}</td>
                    <td>{{ $registration->origin_school_name ?: '—' }}</td>
                    <td>{{ $registration->wave?->name ?: '—' }}</td>
                    <td class="center"><span class="badge">{{ $statusLabels[$registration->status] ?? $registration->status }}</span></td>
                    <td class="center muted">{{ $registration->created_at?->timezone('Asia/Jakarta')->format('d/m/Y') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="center" style="padding:16px;">Tidak ada pendaftar pada filter ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <span class="left">Dicetak: {{ $printedAt->format('d/m/Y H:i:s') }} WIB oleh {{ $printedBy?->name ?? 'Sistem' }}</span>
        <span class="right">Rekap PPDB &mdash; {{ $registrations->count() }} pendaftar</span>
    </div>
</body>
</html>
