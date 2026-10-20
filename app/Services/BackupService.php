<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class BackupService
{
    public function create(?int $userId = null): array
    {
        $filename = 'madani-'.now()->format('Ymd-His').'.sql.gz';
        $sql = $this->dumpDatabase();
        if (! str_contains($sql, 'CREATE TABLE') && ! str_contains($sql, 'INSERT INTO')) {
            throw new \RuntimeException('Backup kosong — dump tidak menghasilkan data');
        }

        Storage::disk('backups')->makeDirectory('');
        $gz = gzencode($sql, 9);
        Storage::disk('backups')->put($filename, $gz);

        $size = Storage::disk('backups')->size($filename);

        ActivityLogger::log('backup.create', $userId ?? auth()->id(), null, null, [
            'filename' => $filename,
            'size' => $size,
            'status' => 'success',
        ]);

        return ['filename' => $filename, 'size' => $size];
    }

    private function dumpDatabase(): string
    {
        $driver = config('database.default');
        $header = "-- Madani v2 database backup\n-- Generated: ".now()->toDateTimeString()."\n-- Driver: {$driver}\n\n";
        $header .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

        try {
            if ($driver === 'sqlite') {
                return $header.$this->dumpSqlite();
            }
            if (in_array($driver, ['mysql', 'mariadb'])) {
                $dump = $this->tryMysqldump();
                if ($dump !== null) {
                    return $header.$dump;
                }

                return $header.$this->dumpMysqlPhp();
            }

            return $header.$this->dumpGeneric();
        } catch (\Throwable $e) {
            Log::error('backup dump failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    private function tryMysqldump(): ?string
    {
        $which = trim((string) @shell_exec('which mysqldump 2>/dev/null'));
        if ($which === '' && PHP_OS_FAMILY === 'Windows') {
            $which = trim((string) @shell_exec('where mysqldump 2>nul'));
        }
        if ($which === '') {
            return null;
        }

        $c = config('database.connections.'.config('database.default'));
        $host = $c['host'] ?? '127.0.0.1';
        $port = $c['port'] ?? '3306';
        $db = $c['database'] ?? '';
        $user = $c['username'] ?? '';
        $pass = $c['password'] ?? '';
        if ($db === '' || $user === '') {
            return null;
        }

        $cmd = sprintf(
            'mysqldump --host=%s --port=%s --user=%s --single-transaction --quick --skip-lock-tables %s 2>&1',
            escapeshellarg($host),
            escapeshellarg((string) $port),
            escapeshellarg($user),
            escapeshellarg($db)
        );
        if ($pass !== '') {
            $cmd = 'MYSQL_PWD='.escapeshellarg($pass).' '.$cmd;
        }

        $out = @shell_exec($cmd);
        if ($out === null || $out === '' || str_contains($out, 'mysqldump: Got error')) {
            return null;
        }

        return $out;
    }

    private function dumpSqlite(): string
    {
        $sql = '';
        $tables = DB::select("SELECT name, sql FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' AND name != 'migrations' ORDER BY name");
        foreach ($tables as $t) {
            $name = $t->name;
            $create = $t->sql;
            if (! $create) {
                continue;
            }
            $sql .= "DROP TABLE IF EXISTS \"{$name}\";\n{$create};\n\n";
            $rows = DB::table($name)->get();
            foreach ($rows as $row) {
                $data = (array) $row;
                $cols = implode('","', array_map(fn ($c) => str_replace('"', '""', $c), array_keys($data)));
                $vals = implode(',', array_map(fn ($v) => $v === null ? 'NULL' : DB::connection()->getPdo()->quote((string) $v), array_values($data)));
                $sql .= "INSERT INTO \"{$name}\" (\"{$cols}\") VALUES ({$vals});\n";
            }
            $sql .= "\n";
        }

        return $sql;
    }

    private function dumpMysqlPhp(): string
    {
        $sql = '';
        $tables = DB::select('SHOW TABLES');
        $key = array_key_first((array) $tables[0] ?? []);
        $names = array_values(array_filter(array_map(function (object $row) use ($key): ?string {
            $data = (array) $row;

            return $data[$key] ?? (array_values($data)[0] ?? null);
        }, $tables)));
        foreach ($names as $name) {
            if ($name === 'migrations') {
                continue;
            }
            $create = DB::selectOne("SHOW CREATE TABLE `{$name}`");
            $createSql = $create->{'Create Table'} ?? array_values((array) $create)[1] ?? '';
            $sql .= "DROP TABLE IF EXISTS `{$name}`;\n{$createSql};\n\n";
            $rows = DB::table($name)->get();
            foreach ($rows as $row) {
                $data = (array) $row;
                $cols = implode('`,`', array_keys($data));
                $vals = implode(',', array_map(fn ($v) => $v === null ? 'NULL' : DB::connection()->getPdo()->quote((string) $v), array_values($data)));
                $sql .= "INSERT INTO `{$name}` (`{$cols}`) VALUES ({$vals});\n";
            }
            $sql .= "\n";
        }

        return $sql;
    }

    private function dumpGeneric(): string
    {
        $sql = "-- Generic fallback: data only (schema not dumped for this driver)\n\n";
        $tables = ['users', 'classrooms', 'subjects', 'students', 'teacher_subjects', 'schedules', 'attendances', 'teacher_attendances', 'scores', 'activity_logs', 'site_settings', 'school_principals'];
        foreach ($tables as $name) {
            try {
                $rows = DB::table($name)->get();
            } catch (\Throwable) {
                continue;
            }
            foreach ($rows as $row) {
                $data = (array) $row;
                $cols = implode('","', array_keys($data));
                $vals = implode(',', array_map(fn ($v) => $v === null ? 'NULL' : DB::connection()->getPdo()->quote((string) $v), array_values($data)));
                $sql .= "INSERT INTO \"{$name}\" (\"{$cols}\") VALUES ({$vals});\n";
            }
            $sql .= "\n";
        }

        return $sql;
    }
}
