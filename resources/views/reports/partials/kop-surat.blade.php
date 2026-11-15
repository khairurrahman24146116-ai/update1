@php
    $kopPath = public_path('images/kopsurat/kop-surat.png');
    $kopSrc = is_file($kopPath)
        ? 'data:image/png;base64,'.base64_encode((string) file_get_contents($kopPath))
        : '';
    $kopWidthPx = $kopWidthPx ?? 741;
    $kopHeightPx = (int) round($kopWidthPx * 467 / 2481);
@endphp

<div class="kop-surat">
    <img src="{{ $kopSrc }}" alt="Kop Surat Yayasan Dayah Madani Al-Aziziyah" width="{{ $kopWidthPx }}" height="{{ $kopHeightPx }}" style="width: {{ $kopWidthPx }}px; height: {{ $kopHeightPx }}px;">
</div>
