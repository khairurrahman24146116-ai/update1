<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Log Aktivitas</title>
    <style>
        @page { margin: 24px 24px 34px; }
        * { box-sizing: border-box; }
        body { font-family: "DejaVu Sans", sans-serif; font-size: 8.5px; color: #1f2937; }
        h1 { font-size: 15px; margin: 0; text-align: center; text-transform: uppercase; letter-spacing: .4px; }
        h2 { font-size: 12px; margin: 4px 0 0; text-align: center; font-weight: normal; }
        .meta { margin: 12px 0 10px; padding: 8px 10px; border: 1px solid #d1d5db; background: #f9fafb; }
        .meta p { margin: 2px 0; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th, td { border: 1px solid #cbd5e1; padding: 4px 5px; vertical-align: top; word-wrap: break-word; }
        th { background: #e5e7eb; font-size: 8px; text-transform: uppercase; text-align: left; }
        tr:nth-child(even) td { background: #f8fafc; }
        .muted { color: #64748b; }
        .footer { position: fixed; bottom: -20px; left: 0; right: 0; font-size: 7.5px; color: #64748b; }
        .footer .left { float: left; }
        .footer .right { float: right; }
    </style>
</head>
<body>
    <h1>{{ $appName }}</h1>
    <h2>Laporan Log Aktivitas</h2>

    <div class="meta">
        @if (! empty($filters['search']))
            <p><strong>Pencarian:</strong> {{ $filters['search'] }}</p>
        @endif
        @if (! empty($filters['module']))
            <p><strong>Modul:</strong> {{ \App\Support\ActivityLogCatalog::moduleLabel($filters['module']) }}</p>
        @endif
        @if (! empty($filters['action']))
            <p><strong>Aksi:</strong> {{ \App\Support\ActivityLogCatalog::descriptionFor($filters['action']) }} ({{ $filters['action'] }})</p>
        @endif
        @if (! empty($filters['user_id']))
            <p><strong>Pengguna:</strong> ID {{ $filters['user_id'] }}</p>
        @endif
        @if (! empty($filters['from']) || ! empty($filters['to']))
            <p><strong>Periode:</strong> {{ $filters['from'] ?? 'awal' }} s.d. {{ $filters['to'] ?? 'sekarang' }} WIB</p>
        @endif
        <p><strong>Jumlah baris:</strong> {{ $logs->count() }}</p>
        <p><strong>Dicetak:</strong> {{ $printedAt->format('d/m/Y H:i:s') }} WIB oleh {{ $printedBy->name }} ({{ $printedBy->role }})</p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 11%;">Waktu WIB</th>
                <th style="width: 12%;">Pengguna</th>
                <th style="width: 9%;">Role</th>
                <th style="width: 10%;">Modul</th>
                <th style="width: 14%;">Aksi</th>
                <th style="width: 25%;">Deskripsi</th>
                <th style="width: 11%;">Target</th>
                <th style="width: 8%;">IP</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($logs as $log)
                <tr>
                    <td class="muted">{{ $log->created_at?->timezone('Asia/Jakarta')->format('d/m/Y H:i:s') }}</td>
                    <td>{{ $log->user_name ?: 'Sistem' }}</td>
                    <td>{{ $log->user_role ?: '-' }}</td>
                    <td>{{ \App\Support\ActivityLogCatalog::moduleLabel($log->module) }}</td>
                    <td>{{ $log->action }}</td>
                    <td>{{ $log->description }}</td>
                    <td>{{ $log->target_type ? \Illuminate\Support\Str::afterLast($log->target_type, '\\').'#'.$log->target_id : '-' }}</td>
                    <td>{{ $log->ip_address ?: '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="text-align: center; padding: 18px;">Tidak ada log pada filter ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <span class="left">Log Aktivitas — {{ $appName }}</span>
        <span class="right">Dicetak {{ $printedAt->format('d/m/Y H:i') }} WIB</span>
    </div>

    <script type="text/php">
        if (isset($pdf)) {
            $pdf->page_text(720, 570, "Halaman {PAGE_NUM} dari {PAGE_COUNT}", null, 8, [0.39, 0.45, 0.55]);
        }
    </script>
</body>
</html>
