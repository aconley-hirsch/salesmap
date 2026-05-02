<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use SQLite3;
use Throwable;

#[Signature('db:backup {--reason=manual : Reason to include in the backup filename} {--skip-remote : Only create the local backup}')]
#[Description('Create a compressed SQLite database backup and optionally upload it with rclone.')]
class BackupDatabase extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $sourcePath = $this->databasePath();

        if ($sourcePath === ':memory:' || ! File::isFile($sourcePath)) {
            $this->error("SQLite database not found at [{$sourcePath}].");

            return self::FAILURE;
        }

        if (! class_exists(SQLite3::class)) {
            $this->error('The SQLite3 PHP extension is required to create online database backups.');

            return self::FAILURE;
        }

        $backupDirectory = (string) config('backup.local_path', storage_path('app/backups/database'));
        File::ensureDirectoryExists($backupDirectory);

        $reason = str((string) $this->option('reason'))->slug()->value() ?: 'manual';
        $backupPath = $backupDirectory.'/'.now()->format('Y-m-d-His').'-'.$reason.'.sqlite.gz';
        $snapshotPath = $backupPath.'.tmp';

        try {
            $this->snapshotDatabase($sourcePath, $snapshotPath);
            $this->compressSnapshot($snapshotPath, $backupPath);
        } catch (Throwable $exception) {
            File::delete([$snapshotPath, $backupPath]);

            $this->error('Unable to create database backup: '.$exception->getMessage());

            return self::FAILURE;
        } finally {
            File::delete($snapshotPath);
        }

        $this->info('Created local backup: '.$backupPath);

        $this->pruneLocalBackups($backupDirectory);

        if (! $this->option('skip-remote') && (bool) config('backup.remote.enabled')) {
            if (! $this->uploadRemoteBackup($backupPath)) {
                return self::FAILURE;
            }

            if (! $this->pruneRemoteBackups()) {
                return self::FAILURE;
            }
        }

        return self::SUCCESS;
    }

    private function databasePath(): string
    {
        return (string) (config('backup.sqlite_path') ?: config('database.connections.sqlite.database'));
    }

    private function snapshotDatabase(string $sourcePath, string $snapshotPath): void
    {
        $source = new SQLite3($sourcePath, SQLITE3_OPEN_READONLY);
        $destination = new SQLite3($snapshotPath, SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);

        try {
            if (! $source->backup($destination)) {
                throw new \RuntimeException('SQLite backup API returned false.');
            }
        } finally {
            $destination->close();
            $source->close();
        }
    }

    private function compressSnapshot(string $snapshotPath, string $backupPath): void
    {
        $source = fopen($snapshotPath, 'rb');
        $destination = gzopen($backupPath, 'wb9');

        if ($source === false || $destination === false) {
            if (is_resource($source)) {
                fclose($source);
            }

            if (is_resource($destination)) {
                gzclose($destination);
            }

            throw new \RuntimeException('Unable to open backup files for compression.');
        }

        try {
            while (! feof($source)) {
                gzwrite($destination, fread($source, 1024 * 1024));
            }
        } finally {
            fclose($source);
            gzclose($destination);
        }
    }

    private function uploadRemoteBackup(string $backupPath): bool
    {
        $remotePath = $this->remoteDirectory().'/'.basename($backupPath);
        $result = Process::timeout(300)->run(['rclone', 'copyto', $backupPath, $remotePath]);

        if ($result->failed()) {
            $this->error('Remote backup upload failed: '.$result->errorOutput());

            return false;
        }

        $this->info('Uploaded remote backup: '.$remotePath);

        return true;
    }

    private function pruneLocalBackups(string $backupDirectory): void
    {
        $cutoff = now()->subDays($this->retentionDays())->getTimestamp();

        collect(File::glob($backupDirectory.'/*.sqlite.gz') ?: [])
            ->filter(fn (string $path): bool => File::lastModified($path) < $cutoff)
            ->each(fn (string $path): bool => File::delete($path));
    }

    private function pruneRemoteBackups(): bool
    {
        $result = Process::timeout(300)->run([
            'rclone',
            'delete',
            $this->remoteDirectory(),
            '--min-age',
            $this->retentionDays().'d',
        ]);

        if ($result->failed()) {
            $this->error('Remote backup pruning failed: '.$result->errorOutput());

            return false;
        }

        return true;
    }

    private function remoteDirectory(): string
    {
        return rtrim((string) config('backup.remote.rclone_remote', 'r2:hirsch-sales-map-backups'), '/').'/database';
    }

    private function retentionDays(): int
    {
        return max(1, (int) config('backup.retention_days', 14));
    }
}
