<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" href="{{ asset('images/profile/logo-lpi-transparent.png') }}">
<title>LPI Dayah Madani Al-Aziziyah — Beranda</title>
<meta name="description" content="Lembaga Pendidikan Islam terpadu: Dayah Salafiyah dan pendidikan formal yang bermutu.">
<meta property="og:site_name" content="LPI Dayah Madani Al-Aziziyah">
<meta name="color-scheme" content="light dark">
<script>
(function () {
  try {
    var stored = localStorage.getItem('lpi-theme');
    if (stored !== 'light' && stored !== 'dark') {
      stored = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    }
    document.documentElement.setAttribute('data-theme', stored);
    document.documentElement.style.colorScheme = stored;
  } catch (e) {}
})();
</script>
@vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<div id="app"></div>
</body>
</html>
