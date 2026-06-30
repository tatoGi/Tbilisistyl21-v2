<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

/**
 * Takes a SAFE, repeatable snapshot of the production Payload CMS database on
 * Neon and restores it into a LOCAL "legacy" database.
 *
 * Safety guarantees:
 *  - Production Neon is only ever read, via `pg_dump` (no writes, no schema push).
 *  - The dump is stored as a timestamped file under storage/legacy-backups so
 *    you keep a history of backups.
 *  - The restore target is a throwaway LOCAL database; the ETL then reads from
 *    that copy, so production is never touched again after the dump.
 */
class LegacySnapshot extends Command
{
    protected $signature = 'legacy:snapshot {--keep=10 : How many dump files to retain}';

    protected $description = 'Read-only pg_dump of Neon -> local "legacy" DB (safe & repeatable)';

    public function handle(): int
    {
        $neonUrl = config('database.connections.legacy.neon_url');
        if (!$neonUrl) {
            $this->error('NEON_DATABASE_URL is not set in .env.');
            $this->line('Add the read-only Neon connection string, e.g.:');
            $this->line('  NEON_DATABASE_URL=postgresql://user:pass@host/db?sslmode=require');
            return self::FAILURE;
        }

        $local = config('database.connections.legacy');
        $dir = storage_path('legacy-backups');
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $dumpFile = $dir . '/neon-' . now()->format('Ymd-His') . '.dump';

        // 1) Read-only dump from Neon.
        $this->info('1/3  pg_dump from Neon (read-only)...');
        $dump = Process::timeout(900)->run([
            'pg_dump', $neonUrl,
            '--no-owner', '--no-privileges', '--no-acl',
            '--format=custom',
            '--file=' . $dumpFile,
        ]);
        if (!$dump->successful()) {
            $this->error('pg_dump failed: ' . $dump->errorOutput());
            return self::FAILURE;
        }
        $this->line('     saved: ' . $dumpFile . ' (' . $this->humanSize($dumpFile) . ')');

        $env = ['PGPASSWORD' => (string) $local['password']];
        $base = ['-h', (string) $local['host'], '-p', (string) $local['port'], '-U', (string) $local['username']];
        $db = (string) $local['database'];

        // 2) Recreate the local legacy database (throwaway mirror).
        $this->info('2/3  recreating local "' . $db . '" database...');
        $drop = Process::timeout(120)->env($env)->run(array_merge(
            ['psql'], $base, ['-d', 'postgres', '-v', 'ON_ERROR_STOP=1', '-c', 'DROP DATABASE IF EXISTS "' . $db . '" WITH (FORCE);']
        ));
        if (!$drop->successful()) {
            $this->error('drop database failed: ' . $drop->errorOutput());
            return self::FAILURE;
        }
        $create = Process::timeout(120)->env($env)->run(array_merge(
            ['psql'], $base, ['-d', 'postgres', '-v', 'ON_ERROR_STOP=1', '-c', 'CREATE DATABASE "' . $db . '";']
        ));
        if (!$create->successful()) {
            $this->error('create database failed: ' . $create->errorOutput());
            return self::FAILURE;
        }

        // 3) Restore the dump into the local copy.
        $this->info('3/3  restoring snapshot into local "' . $db . '"...');
        $restore = Process::timeout(900)->env($env)->run(array_merge(
            ['pg_restore'], $base, ['-d', $db, '--no-owner', '--no-privileges', $dumpFile]
        ));
        // pg_restore returns non-zero on benign warnings; surface them but don't fail hard.
        if (trim($restore->errorOutput()) !== '') {
            $this->warn('pg_restore notices:');
            $this->line($restore->errorOutput());
        }

        $this->pruneOldDumps($dir, (int) $this->option('keep'));

        $this->newLine();
        $this->info('Snapshot complete. Production Neon was only read.');
        $this->line('Next: php artisan legacy:inspect');

        return self::SUCCESS;
    }

    private function humanSize(string $file): string
    {
        $bytes = is_file($file) ? filesize($file) : 0;
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 1) . ' ' . $units[$i];
    }

    private function pruneOldDumps(string $dir, int $keep): void
    {
        if ($keep <= 0) {
            return;
        }
        $files = glob($dir . '/neon-*.dump') ?: [];
        rsort($files);
        foreach (array_slice($files, $keep) as $old) {
            @unlink($old);
        }
    }
}
