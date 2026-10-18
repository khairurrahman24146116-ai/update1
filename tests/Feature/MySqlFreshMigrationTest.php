<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PDO;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Tests\TestCase;

class MySqlFreshMigrationTest extends TestCase
{
    #[RunInSeparateProcess]
    public function test_migrate_fresh_succeeds_on_empty_mysql_database(): void
    {
        $base = config('database.connections.mysql');

        if (! is_array($base)) {
            $this->markTestSkipped('Konfigurasi koneksi mysql tidak tersedia.');
        }

        $host = $base['host'] ?? '127.0.0.1';
        $port = $base['port'] ?? 3306;
        $username = $base['username'] ?? null;
        $password = $base['password'] ?? null;

        try {
            $pdo = new PDO(
                "mysql:host={$host};port={$port}",
                $username,
                $password,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
            );
        } catch (\Throwable $e) {
            $this->markTestSkipped('MySQL tidak dapat dijangkau: '.$e->getMessage());
        }

        $scratch = 'madaniv2_fresh_test_'.bin2hex(random_bytes(4));

        try {
            $pdo->exec("CREATE DATABASE `{$scratch}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        } catch (\Throwable $e) {
            $this->markTestSkipped('Tidak dapat membuat database sementara: '.$e->getMessage());
        }

        config()->set('database.connections.mysql.database', $scratch);
        config()->set('database.default', 'mysql');
        DB::purge('mysql');

        try {
            $exitCode = Artisan::call('migrate:fresh', ['--force' => true]);
            $output = Artisan::output();

            $this->assertSame(0, $exitCode, "migrate:fresh gagal:\n".$output);
            $this->assertTrue(
                Schema::connection('mysql')->hasTable('p_p_d_b_registrations'),
                'Tabel p_p_d_b_registrations tidak terbentuk.',
            );
            $this->assertTrue(
                Schema::connection('mysql')->hasColumn('p_p_d_b_registrations', 'guardian_birth_date'),
                'Kolom guardian_birth_date tidak terbentuk.',
            );
        } finally {
            DB::purge('mysql');
            $pdo->exec("DROP DATABASE IF EXISTS `{$scratch}`");
        }
    }
}
