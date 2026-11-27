@php
    $kopPath = public_path('images/kopsurat/kop-surat-pdf.jpg');
    if (! is_file($kopPath)) {
        $kopPath = public_path('images/kopsurat/kop-surat.png');
    }

    $kopSrc = '';
    if (is_file($kopPath)) {
        $cacheKey = 'pdf_kop_surat_'.md5($kopPath.'_'.filemtime($kopPath));
        $kopSrc = \Illuminate\Support\Facades\Cache::rememberForever($cacheKey, function () use ($kopPath): string {
            $mime = strtolower((string) pathinfo($kopPath, PATHINFO_EXTENSION)) === 'png' ? 'image/png' : 'image/jpeg';
            $contents = @file_get_contents($kopPath);
            if ($contents === false) {
                return '';
            }

            return 'data:'.$mime.';base64,'.base64_encode($contents);
        });
    }

    $kopWidthPx = $kopWidthPx ?? 741;
    $kopHeightPx = (int) round($kopWidthPx * 467 / 2481);
@endphp

<div class="kop-surat">
    <img src="{{ $kopSrc }}" alt="Kop Surat Yayasan Dayah Madani Al-Aziziyah" width="{{ $kopWidthPx }}" height="{{ $kopHeightPx }}" style="width: {{ $kopWidthPx }}px; height: {{ $kopHeightPx }}px;">
</div>
