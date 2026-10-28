# LAPORAN STATUS PROJECT madaniv2

## 1. Ringkasan Eksekutif
- **Persentase Keseluruhan Project: 75%**
- **5 Hal yang Sudah Bagus:**
    1. Otorisasi modul Akhlak/Welfare telah diimplementasikan dengan `EnsureAkhlakAccess` dan scope unit yang kuat.
    2. Import santri telah di-upgrade menjadi `all-or-nothing` dengan validasi ketat dan template XLSX yang informatif.
    3. Struktur project menggunakan Laravel 13 dan sudah menerapkan middleware/policy untuk akses data yang memisahkan unit LPI, SMA, dan SMP (flag tersembunyi).
    4. Unit testing untuk modul kritikal (welfare, import) telah berjalan dengan cakupan yang cukup (259 tes).
    5. Dashboard guru dan akses mapel sudah terfilter berdasarkan assignment guru (relasi guru-mapel-kelas).

- **5 Masalah Terbesar:**
    1. **Izin pulang** tidak memiliki deteksi keterlambatan kembali, notifikasi, maupun upload bukti fisik (surat dokter/izin).
    2. Modul **Keuangan** belum memiliki validasi akses per unit yang menyeluruh; guru dengan permission finance bisa mengakses data keuangan tanpa pembatasan role yang ketat.
    3. **Timezone UTC** dapat menyebabkan ketidaksinkronan perhitungan kehadiran/eligibility pada jam krusial (tengah malam).
    4. **Data manual** (jabatan, penugasan, ambang batas) sangat tinggi ketergantungannya agar fitur jalan, namun belum ada panduan administratif yang jelas.
    5. Terdapat **N+1/query tanpa filter unit** pada beberapa endpoint dashboard yang dapat menyebabkan kebocoran data jika tidak diproteksi dengan scope.

## 2. Tabel per Modul

| Modul | Status | Persen | Bukti | Yang Kurang |
|---|---|---|---|---|
| Akun & Role | Selesai | 95% | `User.php`, `permissions.js` | Audit log koreksi belum detail. |
| Keuangan | Sebagian | 60% | `SppController`, `FinanceApiTest` | Filter unit tidak konsisten, otorisasi bendahara. |
| Akhlak & Izin | Sebagian | 70% | `AkhlakRecordController`, `StudentWelfareService` | Bukti fisik, deteksi terlambat, notifikasi. |
| Absensi & Nilai | Selesai | 85% | `AttendanceController`, `ScoreController` | Validasi rentang nilai, rekap multi-unit. |
| Mata Pelajaran | Selesai | 90% | `SubjectController`, `SubjectManagementTest` | Pemisahan formal/nonformal di UI. |
| Dashboard | Selesai | 80% | `GuruDashboardController` | Responsifitas, empty state. |
| Publik & PPDB | Sebagian | 50% | `GalleryAlbumController`, `PPDBRegistrationController` | Rate limit, validasi form publik. |

## 3. Daftar Bug & Risiko

- **Kritis:** 
    - `allow_permit_override` (Setting): Mengaktifkan/menonaktifkan setting ini memblokir seluruh approval izin, bukan hanya override. (Lokasi: `StudentWelfareService.php`)
- **Tinggi:** 
    - Deteksi terlambat kembali izin: Tidak ada scheduler/job/alert. (Lokasi: `routes/console.php`, `App\Console`)
    - Otorisasi Bendahara: Filter unit tidak konsisten di seluruh route keuangan. (Lokasi: `FinanceTransactionController`)
- **Sedang:** 
    - Timezone UTC: Perhitungan eligibilitas/kehadiran bisa bias tanggal di zona lokal. (Lokasi: `config/app.php`)
    - Audit log koreksi: Hanya mencatat field, bukan nilai lama/baru. (Lokasi: `AkhlakRecordController`)

## 4. Daftar Belum Dibuat
1. Deteksi otomatis izin terlambat kembali + Notifikasi.
2. Fitur upload bukti izin/sakit.
3. Sinkronisasi timezone lokal (WIB).
4. Pemisahan CRUD mapel formal/nonformal di UI.
5. Modal update akhlak (saat ini `window.prompt`).

## 5. Checklist Siap Rilis
- [ ] Set `APP_TIMEZONE` ke `Asia/Jakarta`.
- [ ] Pastikan `.env` memiliki `ENABLED_UNITS` yang benar (`lpi,sma`).
- [ ] Isi data: Unit, Jabatan Kabag, Penugasan Guru, Wali-Santri.
- [ ] Jalankan scheduler di server agar cron berjalan.
- [ ] Cek backup database di server.

## 6. Langkah Setting Data
1. Jalankan `seeder` untuk unit dan konfigurasi dasar.
2. Tambahkan user Kabag Pengajian, beri role guru dan pivot jabatan 'Kabag Pengajian'.
3. Isi kelas LPI/SMA/SMP dan pastikan `unit` diisi.
4. Hubungkan santri dengan `parent_id` untuk portal wali.
5. Atur `student_welfare_settings` (ambang batas).

## 7. Rekomendasi Selanjutnya
1. Fix bug approval izin (High).
2. Bangun scheduler/job deteksi terlambat kembali.
3. Audit filter unit di seluruh route Keuangan.
4. Tambahkan fitur upload bukti izin.
5. Perbaikan UX (ganti `window.prompt` dengan Modal).

---
*Laporan dibuat tanpa mengubah kode atau data.*
*Pengecekan: `php artisan route:list`, `grep`, inspeksi model/service/test.*
