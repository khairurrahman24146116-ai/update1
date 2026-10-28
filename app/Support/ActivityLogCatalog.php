<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class ActivityLogCatalog
{
    /**
     * Modul audit beserta label yang ditampilkan di UI.
     *
     * @var array<string, string>
     */
    public const MODULES = [
        'auth' => 'Autentikasi',
        'students' => 'Data Santri',
        'teachers' => 'Data Guru',
        'classrooms' => 'Kelas',
        'subjects' => 'Mata Pelajaran',
        'schedules' => 'Jadwal Pelajaran',
        'attendance' => 'Absensi',
        'scores' => 'Nilai',
        'welfare' => 'Kesejahteraan Santri',
        'finance' => 'Keuangan',
        'spp' => 'SPP & Pembayaran',
        'reports' => 'Laporan Keuangan',
        'ppdb' => 'PPDB',
        'content' => 'Konten Publik',
        'settings' => 'Pengaturan Website',
        'users' => 'Manajemen Akun',
        'permissions' => 'Hak Akses',
        'system' => 'Sistem',
        'general' => 'Umum',
    ];

    /**
     * Deskripsi aksi yang dikenal. Aksi baru tetap dicatat dengan label
     * otomatis bila belum terdaftar di sini.
     *
     * @var array<string, string>
     */
    public const ACTIONS = [
        'login' => 'Login berhasil',
        'login.failed' => 'Percobaan login gagal',
        'login.blocked' => 'Login ditolak karena memiliki akses penuh',
        'logout' => 'Logout dari aplikasi',

        'user.create' => 'Menambah akun pengguna',
        'user.update' => 'Mengubah akun pengguna',
        'user.deactivate' => 'Menonaktifkan akun pengguna',
        'user.activate' => 'Mengaktifkan akun pengguna',
        'user.password_reset' => 'Mereset kata sandi akun',
        'user.delete' => 'Menghapus akun pengguna',
        'permission.update' => 'Mengubah hak akses akun',

        'student.create' => 'Menambah data santri',
        'student.update' => 'Mengubah data santri',
        'student.delete' => 'Menghapus data santri',
        'student.enrollment.naik_jenjang' => 'Memproses kenaikan jenjang santri',
        'student.enrollment.manual' => 'Menempatkan santri secara manual',
        'import.preview' => 'Memproses pratinjau impor santri',
        'import.confirm' => 'Mengonfirmasi impor santri',
        'import.failed' => 'Impor santri gagal',
        'import.cancel' => 'Membatalkan impor santri',

        'teacher.create' => 'Menambah profil guru',
        'teacher.update' => 'Mengubah profil guru',
        'teacher.units.update' => 'Mengubah unit & jabatan guru',
        'teacher.assignments.update' => 'Mengubah penugasan & wali kelas guru',
        'teacher.photo.upload' => 'Mengunggah foto guru',
        'teacher_subject.create' => 'Menambah mata pelajaran guru',
        'teacher_subject.delete' => 'Menghapus mata pelajaran guru',
        'classroom.create' => 'Menambah kelas',
        'classroom.update' => 'Mengubah kelas',
        'classroom.delete' => 'Menghapus kelas',
        'subject.create' => 'Menambah mata pelajaran',
        'subject.update' => 'Mengubah mata pelajaran',
        'subject.delete' => 'Menghapus mata pelajaran',

        'schedule.create' => 'Menambah jadwal pelajaran',
        'schedule.update' => 'Mengubah jadwal pelajaran',
        'schedule.delete' => 'Menghapus jadwal pelajaran',
        'attendance.store' => 'Menyimpan absensi siswa',
        'attendance.import.preview' => 'Memproses pratinjau impor absensi',
        'attendance.import' => 'Mengimpor absensi siswa',
        'attendance.teacher.checkin' => 'Check-in kehadiran guru',
        'attendance.teacher.checkout' => 'Check-out kehadiran guru',
        'attendance.teacher.update' => 'Mengubah absensi guru',
        'score.update' => 'Menyimpan nilai santri',

        'akhlak.create' => 'Mencatat akhlak santri',
        'akhlak.update' => 'Mengoreksi catatan akhlak santri',
        'akhlak.cancel' => 'Membatalkan catatan akhlak santri',
        'permit.create' => 'Mengajukan izin santri',
        'permit.approve' => 'Menyetujui izin santri',
        'permit.reject' => 'Menolak izin santri',
        'permit.cancel' => 'Membatalkan izin santri',
        'welfare.settings.update' => 'Mengubah batas kelayakan santri',

        'finance.transaction.create' => 'Menambah transaksi keuangan',
        'finance.transaction.update' => 'Mengubah transaksi keuangan',
        'finance.transaction.delete' => 'Menghapus transaksi keuangan',
        'reports.finance.print' => 'Mencetak laporan keuangan',
        'reports.finance.export' => 'Mengekspor laporan keuangan',

        'spp.invoice.create' => 'Membuat tagihan SPP',
        'spp.invoice.generate' => 'Membuat tagihan SPP massal',
        'spp.payment.create' => 'Mencatat pembayaran SPP',
        'spp.payment.cancel' => 'Membatalkan pembayaran SPP',

        'ppdb_wave.create' => 'Menambah gelombang PPDB',
        'ppdb_wave.update' => 'Mengubah gelombang PPDB',
        'ppdb_wave.delete' => 'Menghapus gelombang PPDB',
        'ppdb_registration.create' => 'Pendaftaran PPDB baru',
        'ppdb_registration.update' => 'Mengubah status pendaftar PPDB',
        'ppdb_registration.delete' => 'Menghapus pendaftar PPDB',

        'news.create' => 'Menambah berita',
        'news.update' => 'Mengubah berita',
        'news.delete' => 'Menghapus berita',
        'news_category.create' => 'Menambah kategori berita',
        'news_category.update' => 'Mengubah kategori berita',
        'news_category.delete' => 'Menghapus kategori berita',
        'gallery.create' => 'Menambah album galeri',
        'gallery.update' => 'Mengubah album galeri',
        'gallery.delete' => 'Menghapus album galeri',
        'gallery.item.create' => 'Menambah foto/video galeri',
        'gallery.item.delete' => 'Menghapus foto/video galeri',
        'principal.create' => 'Menambah data kepala sekolah',
        'principal.update' => 'Mengubah data kepala sekolah',
        'principal.delete' => 'Menghapus data kepala sekolah',
        'organization.create' => 'Menambah anggota organisasi',
        'organization.update' => 'Mengubah anggota organisasi',
        'organization.delete' => 'Menghapus anggota organisasi',
        'organization.upload_photo' => 'Mengunggah foto anggota organisasi',
        'structure.create' => 'Menambah anggota struktur',
        'structure.update' => 'Mengubah anggota struktur',
        'structure.delete' => 'Menghapus anggota struktur',
        'structure.upload_photo' => 'Mengunggah foto anggota struktur',

        'settings.update' => 'Mengubah pengaturan situs',
        'settings.upload' => 'Mengunggah aset situs',
        'homepage.update' => 'Mengubah pengaturan beranda',
        'homepage.upload_logo' => 'Mengunggah logo beranda',
        'homepage.upload_media' => 'Mengunggah media beranda',
        'homepage.delete_media' => 'Menghapus media beranda',
        'landing.create' => 'Menambah section landing page',
        'landing.update' => 'Mengubah section landing page',
        'landing.delete' => 'Menghapus section landing page',

        'backup.create' => 'Membuat backup basis data',
        'backup.failed' => 'Backup basis data gagal',
        'backup.download' => 'Mengunduh backup basis data',
        'system.maintenance' => 'Pemeliharaan sistem',
        'activity_log.export' => 'Mengekspor log aktivitas (CSV)',
        'activity_log.pdf' => 'Mencetak log aktivitas (PDF)',
    ];

    /**
     * Label role untuk filter log.
     *
     * @var array<string, string>
     */
    public const ROLES = [
        'admin' => 'Admin',
        'admin_utama' => 'Admin Utama',
        'operator' => 'Operator',
        'pending' => 'Menunggu Hak Akses',
        'kepala_sekolah' => 'Kepala Sekolah',
        'bendahara' => 'Bendahara',
        'bendahara_lpi' => 'Bendahara LPI',
        'bendahara_sma' => 'Bendahara SMA',
        'guru' => 'Guru',
        'wali_murid' => 'Wali Murid',
    ];

    public static function moduleFor(string $action): string
    {
        return match (true) {
            in_array($action, ['login', 'login.failed', 'login.blocked', 'logout'], true) => 'auth',
            str_starts_with($action, 'import.') || str_starts_with($action, 'student.') => 'students',
            str_starts_with($action, 'teacher_subject.') || str_starts_with($action, 'teacher.') => 'teachers',
            str_starts_with($action, 'classroom.') => 'classrooms',
            str_starts_with($action, 'subject.') => 'subjects',
            str_starts_with($action, 'schedule.') => 'schedules',
            str_starts_with($action, 'attendance.') || str_starts_with($action, 'teacher_attendance.') => 'attendance',
            str_starts_with($action, 'score.') => 'scores',
            str_starts_with($action, 'akhlak.') || str_starts_with($action, 'permit.')
                || str_starts_with($action, 'welfare.') => 'welfare',
            str_starts_with($action, 'finance.') => 'finance',
            str_starts_with($action, 'spp.') => 'spp',
            str_starts_with($action, 'reports.') => 'reports',
            str_starts_with($action, 'ppdb_') => 'ppdb',
            str_starts_with($action, 'news.') || str_starts_with($action, 'news_category.')
                || str_starts_with($action, 'gallery.') || str_starts_with($action, 'principal.')
                || str_starts_with($action, 'organization.') || str_starts_with($action, 'structure.') => 'content',
            str_starts_with($action, 'settings.') || str_starts_with($action, 'homepage.')
                || str_starts_with($action, 'landing.') => 'settings',
            str_starts_with($action, 'user.') => 'users',
            str_starts_with($action, 'permission.') => 'permissions',
            str_starts_with($action, 'backup.') || str_starts_with($action, 'system.')
                || str_starts_with($action, 'activity_log.') => 'system',
            default => 'general',
        };
    }

    public static function moduleLabel(?string $module): string
    {
        return self::MODULES[$module ?? ''] ?? self::MODULES['general'];
    }

    public static function descriptionFor(string $action): string
    {
        return self::ACTIONS[$action] ?? Str::headline(str_replace(['.', '_'], ' ', $action));
    }

    public static function roleLabel(?string $role): string
    {
        return self::ROLES[$role ?? ''] ?? ($role ?: '-');
    }

    /**
     * @return array<int, array{key: string, label: string}>
     */
    public static function moduleOptions(): array
    {
        return collect(self::MODULES)
            ->map(fn (string $label, string $key): array => ['key' => $key, 'label' => $label])
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>
     */
    public static function sanitizeMetadata(array $metadata): array
    {
        $clean = [];

        foreach ($metadata as $key => $value) {
            $normalized = Str::lower((string) $key);

            if (self::isSensitiveKey($normalized)) {
                continue;
            }

            if ($value instanceof UploadedFile) {
                $clean[$key] = '[file]';

                continue;
            }

            if (is_array($value)) {
                $clean[$key] = self::sanitizeMetadata($value);

                continue;
            }

            if (self::isPhoneKey($normalized) && is_scalar($value)) {
                $clean[$key] = self::maskPhone((string) $value);

                continue;
            }

            $clean[$key] = is_object($value) ? '[object]' : $value;
        }

        return $clean;
    }

    public static function maskPhone(string $phone): string
    {
        $phone = trim($phone);

        if ($phone === '') {
            return '';
        }

        if (mb_strlen($phone) <= 6) {
            return str_repeat('*', mb_strlen($phone));
        }

        return mb_substr($phone, 0, 4).'****'.mb_substr($phone, -3);
    }

    private static function isSensitiveKey(string $key): bool
    {
        if (in_array($key, ['password', 'password_confirmation', 'current_password', 'new_password', 'token', 'access_token', 'refresh_token', 'remember_token', 'authorization', 'otp', 'secret', 'api_key', 'file', 'photo', 'proof'], true)) {
            return true;
        }

        return str_ends_with($key, '_password')
            || str_ends_with($key, '_token')
            || str_ends_with($key, '_secret');
    }

    private static function isPhoneKey(string $key): bool
    {
        return (bool) preg_match('/(^|_)(phone|hp|whatsapp|wa)(_|$)/', $key);
    }
}
