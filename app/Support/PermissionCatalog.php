<?php

namespace App\Support;

/**
 * Katalog izin aplikasi.
 *
 * Satu-satunya sumber kebenaran untuk daftar modul, aksi, label, dan izin
 * bawaan setiap role. Role tetap menjadi identitas akun; izin bawaan role
 * hanya menjadi titik awal yang dapat ditambah/dicabut per akun lewat tabel
 * `user_permissions`.
 */
class PermissionCatalog
{
    /**
     * Modul beserta aksi dan labelnya.
     *
     * @var array<string, array{label: string, description: string, permissions: array<string, string>}>
     */
    public const MODULES = [
        'dashboard' => [
            'label' => 'Dashboard & Statistik',
            'description' => 'Ringkasan data dan statistik pada halaman utama aplikasi.',
            'permissions' => [
                'dashboard.view' => 'Melihat dashboard',
            ],
        ],
        'students' => [
            'label' => 'Data Santri',
            'description' => 'Data santri, status, dan wali murid.',
            'permissions' => [
                'students.view' => 'Melihat data santri',
                'students.create' => 'Menambah santri',
                'students.update' => 'Mengedit santri',
                'students.delete' => 'Menghapus santri',
                'students.import' => 'Mengimpor santri (XLSX)',
                'students.export' => 'Mengekspor data santri',
            ],
        ],
        'teachers' => [
            'label' => 'Data Guru',
            'description' => 'Akun guru/asatidz dan status aktifnya.',
            'permissions' => [
                'teachers.view' => 'Melihat data guru',
                'teachers.create' => 'Menambah guru',
                'teachers.update' => 'Mengedit guru',
                'teachers.delete' => 'Menghapus guru',
            ],
        ],
        'classrooms' => [
            'label' => 'Kelas',
            'description' => 'Master kelas, tingkat, dan tahun ajaran.',
            'permissions' => [
                'classrooms.view' => 'Melihat kelas',
                'classrooms.create' => 'Menambah kelas',
                'classrooms.update' => 'Mengedit kelas',
                'classrooms.delete' => 'Menghapus kelas',
            ],
        ],
        'subjects' => [
            'label' => 'Mata Pelajaran',
            'description' => 'Master mata pelajaran dan kode.',
            'permissions' => [
                'subjects.view' => 'Melihat mata pelajaran',
                'subjects.create' => 'Menambah mata pelajaran',
                'subjects.update' => 'Mengedit mata pelajaran',
                'subjects.delete' => 'Menghapus mata pelajaran',
            ],
        ],
        'schedules' => [
            'label' => 'Jadwal Pelajaran',
            'description' => 'Jadwal mengajar per kelas dan guru.',
            'permissions' => [
                'schedules.view' => 'Melihat jadwal',
                'schedules.create' => 'Menambah jadwal',
                'schedules.update' => 'Mengedit jadwal',
                'schedules.delete' => 'Menghapus jadwal',
            ],
        ],
        'attendance' => [
            'label' => 'Absensi',
            'description' => 'Absensi siswa per jadwal dan absensi kehadiran guru.',
            'permissions' => [
                'attendance.view' => 'Melihat absensi',
                'attendance.create' => 'Menyimpan absensi siswa',
                'attendance.import' => 'Mengimpor absensi (XLSX)',
                'attendance.teacher.view' => 'Melihat absensi guru',
                'attendance.teacher.checkin' => 'Check-in / check-out guru',
            ],
        ],
        'scores' => [
            'label' => 'Nilai',
            'description' => 'Lembar nilai per kelas dan mata pelajaran.',
            'permissions' => [
                'scores.view' => 'Melihat nilai',
                'scores.update' => 'Menyimpan nilai',
            ],
        ],
        'finance' => [
            'label' => 'Keuangan',
            'description' => 'Dashboard keuangan, pemasukan, pengeluaran, dan kategori.',
            'permissions' => [
                'finance.view' => 'Melihat dashboard keuangan',
                'finance.transactions.view' => 'Melihat pemasukan/pengeluaran',
                'finance.transactions.create' => 'Menambah pemasukan/pengeluaran',
                'finance.transactions.update' => 'Mengedit pemasukan/pengeluaran',
                'finance.transactions.delete' => 'Menghapus pemasukan/pengeluaran',
                'finance.categories.view' => 'Melihat kategori keuangan',
                'finance.categories.create' => 'Menambah kategori keuangan',
            ],
        ],
        'spp' => [
            'label' => 'SPP & Pembayaran',
            'description' => 'Tagihan SPP, pembayaran, tunggakan, dan kuitansi.',
            'permissions' => [
                'spp.view' => 'Melihat tagihan & pembayaran SPP',
                'spp.create' => 'Membuat / generate tagihan SPP',
                'spp.update' => 'Mencatat pembayaran SPP',
                'spp.delete' => 'Membatalkan pembayaran SPP',
            ],
        ],
        'reports' => [
            'label' => 'Laporan Keuangan',
            'description' => 'Laporan pemasukan, pengeluaran, dan SPP.',
            'permissions' => [
                'reports.finance.view' => 'Melihat laporan keuangan',
                'reports.finance.export' => 'Mengekspor laporan keuangan',
            ],
        ],
        'ppdb' => [
            'label' => 'PPDB',
            'description' => 'Gelombang PPDB dan data pendaftar.',
            'permissions' => [
                'ppdb.waves.view' => 'Melihat gelombang PPDB',
                'ppdb.waves.create' => 'Menambah gelombang PPDB',
                'ppdb.waves.update' => 'Mengedit gelombang PPDB',
                'ppdb.waves.delete' => 'Menghapus gelombang PPDB',
                'ppdb.registrations.view' => 'Melihat pendaftar',
                'ppdb.registrations.update' => 'Mengubah status pendaftar',
                'ppdb.registrations.delete' => 'Menghapus pendaftar',
                'ppdb.registrations.export' => 'Mengekspor pendaftar',
            ],
        ],
        'content' => [
            'label' => 'Konten Publik',
            'description' => 'Berita, kategori, galeri, dan halaman publik.',
            'permissions' => [
                'content.news.view' => 'Melihat berita',
                'content.news.create' => 'Menambah berita',
                'content.news.update' => 'Mengedit berita',
                'content.news.delete' => 'Menghapus berita',
                'content.news.publish' => 'Memublikasikan berita',
                'content.news_categories.view' => 'Melihat kategori berita',
                'content.news_categories.create' => 'Menambah kategori berita',
                'content.news_categories.update' => 'Mengedit kategori berita',
                'content.news_categories.delete' => 'Menghapus kategori berita',
                'content.gallery.view' => 'Melihat galeri',
                'content.gallery.create' => 'Menambah album/foto galeri',
                'content.gallery.update' => 'Mengedit galeri',
                'content.gallery.delete' => 'Menghapus galeri',
                'content.structure.view' => 'Melihat struktur organisasi',
                'content.structure.create' => 'Menambah anggota struktur',
                'content.structure.update' => 'Mengedit anggota struktur',
                'content.structure.delete' => 'Menghapus anggota struktur',
                'content.principal.view' => 'Melihat data kepala sekolah',
                'content.principal.create' => 'Menambah kepala sekolah',
                'content.principal.update' => 'Mengedit kepala sekolah',
                'content.principal.delete' => 'Menghapus kepala sekolah',
            ],
        ],
        'settings' => [
            'label' => 'Pengaturan Website',
            'description' => 'Identitas lembaga, beranda, dan konten landing page.',
            'permissions' => [
                'settings.site.view' => 'Melihat pengaturan situs',
                'settings.site.manage' => 'Mengubah pengaturan situs',
                'settings.homepage.view' => 'Melihat pengaturan beranda',
                'settings.homepage.manage' => 'Mengubah pengaturan beranda',
                'settings.landing.view' => 'Melihat konten landing page',
                'settings.landing.manage' => 'Mengubah konten landing page',
            ],
        ],
        'users' => [
            'label' => 'Manajemen Akun',
            'description' => 'Akun pengguna lintas role.',
            'permissions' => [
                'users.view' => 'Melihat daftar akun',
                'users.create' => 'Menambah akun',
                'users.update' => 'Mengedit akun',
                'users.delete' => 'Menghapus akun',
            ],
        ],
        'permissions' => [
            'label' => 'Hak Akses',
            'description' => 'Pengaturan izin per akun.',
            'permissions' => [
                'permissions.view' => 'Melihat hak akses akun',
                'permissions.manage' => 'Mengubah hak akses akun',
            ],
        ],
        'activity_logs' => [
            'label' => 'Log Aktivitas',
            'description' => 'Riwayat aktivitas pengguna, ekspor, dan retensi log.',
            'permissions' => [
                'logs.view' => 'Melihat log aktivitas',
            ],
        ],
        'system' => [
            'label' => 'Sistem',
            'description' => 'Backup dan pemeliharaan basis data.',
            'permissions' => [
                'system.backup.create' => 'Membuat backup basis data',
                'system.backup.download' => 'Mengunduh backup basis data',
            ],
        ],
    ];

    /**
     * Izin yang hanya boleh dipegang admin. Izin ini tidak dapat diberikan
     * kepada akun non-admin lewat halaman hak akses.
     *
     * @var list<string>
     */
    public const PRIVILEGED = [
        'students.import',
        'users.view',
        'users.create',
        'users.update',
        'users.delete',
        'permissions.view',
        'permissions.manage',
        'logs.view',
        'system.backup.create',
        'system.backup.download',
    ];

    /**
     * Izin bawaan tiap role. Role yang tidak terdaftar tidak memperoleh izin
     * apa pun secara default.
     *
     * @var array<string, list<string>>
     */
    public const ROLE_DEFAULTS = [
        'admin' => [
            'dashboard.view',
            'students.view', 'students.create', 'students.update', 'students.delete', 'students.import', 'students.export',
            'teachers.view', 'teachers.create', 'teachers.update', 'teachers.delete',
            'classrooms.view', 'classrooms.create', 'classrooms.update', 'classrooms.delete',
            'subjects.view', 'subjects.create', 'subjects.update', 'subjects.delete',
            'schedules.view', 'schedules.create', 'schedules.update', 'schedules.delete',
            'attendance.view', 'attendance.import', 'attendance.teacher.view',
            'scores.view', 'scores.update',
            'finance.view',
            'finance.transactions.view', 'finance.transactions.create', 'finance.transactions.update', 'finance.transactions.delete',
            'finance.categories.view', 'finance.categories.create',
            'spp.view', 'spp.create', 'spp.update', 'spp.delete',
            'reports.finance.view', 'reports.finance.export',
            'ppdb.waves.view', 'ppdb.waves.create', 'ppdb.waves.update', 'ppdb.waves.delete',
            'ppdb.registrations.view', 'ppdb.registrations.update', 'ppdb.registrations.delete', 'ppdb.registrations.export',
            'content.news.view', 'content.news.create', 'content.news.update', 'content.news.delete', 'content.news.publish',
            'content.news_categories.view', 'content.news_categories.create', 'content.news_categories.update', 'content.news_categories.delete',
            'content.gallery.view', 'content.gallery.create', 'content.gallery.update', 'content.gallery.delete',
            'content.structure.view', 'content.structure.create', 'content.structure.update', 'content.structure.delete',
            'content.principal.view', 'content.principal.create', 'content.principal.update', 'content.principal.delete',
            'settings.site.view', 'settings.site.manage',
            'settings.homepage.view', 'settings.homepage.manage',
            'settings.landing.view', 'settings.landing.manage',
            'users.view', 'users.create', 'users.update', 'users.delete',
            'permissions.view',
            'logs.view',
            'system.backup.create', 'system.backup.download',
        ],
        'guru' => [
            'dashboard.view',
            'students.view',
            'teachers.view',
            'classrooms.view',
            'subjects.view',
            'schedules.view',
            'attendance.view', 'attendance.create', 'attendance.import',
            'attendance.teacher.view', 'attendance.teacher.checkin',
            'scores.view', 'scores.update',
        ],
        'bendahara_lpi' => [
            'dashboard.view',
            'students.view',
            'teachers.view',
            'classrooms.view',
            'subjects.view',
            'schedules.view',
            'finance.view',
            'finance.transactions.view', 'finance.transactions.create', 'finance.transactions.update', 'finance.transactions.delete',
            'finance.categories.view', 'finance.categories.create',
            'spp.view', 'spp.create', 'spp.update',
            'reports.finance.view', 'reports.finance.export',
        ],
        'bendahara_sma' => [
            'dashboard.view',
            'students.view',
            'teachers.view',
            'classrooms.view',
            'subjects.view',
            'schedules.view',
            'finance.view',
            'finance.transactions.view', 'finance.transactions.create', 'finance.transactions.update', 'finance.transactions.delete',
            'finance.categories.view', 'finance.categories.create',
            'spp.view', 'spp.create', 'spp.update',
            'reports.finance.view', 'reports.finance.export',
        ],
        'bendahara' => [
            'dashboard.view',
            'students.view',
            'teachers.view',
            'classrooms.view',
            'subjects.view',
            'schedules.view',
        ],
        'wali_murid' => [
            'dashboard.view',
            'students.view',
            'teachers.view',
            'classrooms.view',
            'subjects.view',
            'schedules.view',
            'scores.view',
        ],
    ];

    /**
     * @return list<string>
     */
    public static function allPermissions(): array
    {
        $all = [];
        foreach (self::MODULES as $module) {
            foreach (array_keys($module['permissions']) as $permission) {
                $all[] = $permission;
            }
        }

        return $all;
    }

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        $labels = [];
        foreach (self::MODULES as $module) {
            foreach ($module['permissions'] as $permission => $label) {
                $labels[$permission] = $label;
            }
        }

        return $labels;
    }

    public static function exists(string $permission): bool
    {
        return in_array($permission, self::allPermissions(), true);
    }

    /**
     * @return list<string>
     */
    public static function forRole(?string $role): array
    {
        return self::ROLE_DEFAULTS[$role ?? ''] ?? [];
    }

    public static function isPrivileged(string $permission): bool
    {
        return in_array($permission, self::PRIVILEGED, true);
    }

    /**
     * Struktur katalog untuk konsumsi frontend.
     *
     * @return array<int, array{key: string, label: string, description: string, permissions: array<int, array{key: string, label: string, privileged: bool}>}>
     */
    public static function modules(): array
    {
        $out = [];
        foreach (self::MODULES as $key => $module) {
            $permissions = [];
            foreach ($module['permissions'] as $permission => $label) {
                $permissions[] = [
                    'key' => $permission,
                    'label' => $label,
                    'privileged' => self::isPrivileged($permission),
                ];
            }
            $out[] = [
                'key' => $key,
                'label' => $module['label'],
                'description' => $module['description'],
                'permissions' => $permissions,
            ];
        }

        return $out;
    }
}
