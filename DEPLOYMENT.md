# Deployment (Shared Hosting cPanel)

Error `Class "DOMDocument" not found` = ekstensi PHP `dom`/`xml` belum aktif. Bukan bug kode. Jangan pakai `yum`/`pecl` (tanpa root).

## 1. Aktifkan ekstensi di cPanel

1. Login cPanel.
2. Buka Software > Select PHP Version.
3. Pilih PHP 8.3 pada dropdown versi.
4. Centang: `dom`, `xml`, `mbstring`, `fileinfo`.
5. Klik Save.
6. Verifikasi: buka Terminal cPanel, jalankan `php -m | grep -Ei 'dom|xml|mbstring|fileinfo'`.

## 2. Deploy setelah ekstensi aktif

```bash
git pull
composer install --no-dev --optimize-autoloader
php scripts/check-env.php
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Pastikan `.env`: `APP_URL=https://domainkamu.com` tanpa trailing slash.
