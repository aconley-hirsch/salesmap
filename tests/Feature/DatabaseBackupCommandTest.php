<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->backupRoot = storage_path('framework/testing/database-backups/'.Str::uuid());
    $this->databasePath = $this->backupRoot.'/database.sqlite';
    $this->backupPath = $this->backupRoot.'/backups';

    File::ensureDirectoryExists($this->backupRoot);

    config([
        'backup.sqlite_path' => $this->databasePath,
        'backup.local_path' => $this->backupPath,
        'backup.retention_days' => 14,
        'backup.remote.enabled' => false,
        'backup.remote.rclone_remote' => 'r2:hirsch-sales-map-backups',
    ]);
});

afterEach(function () {
    File::deleteDirectory($this->backupRoot);
});

test('it creates a compressed sqlite backup that can be opened', function () {
    createBackupTestDatabase($this->databasePath);

    $this->artisan('db:backup', ['--reason' => 'unit test', '--skip-remote' => true])
        ->assertSuccessful();

    $backups = File::glob($this->backupPath.'/*.sqlite.gz');

    expect($backups)->toHaveCount(1)
        ->and(basename($backups[0]))->toContain('unit-test.sqlite.gz');

    $restoredPath = $this->backupRoot.'/restored.sqlite';
    File::put($restoredPath, gzdecode(File::get($backups[0])));

    $database = new SQLite3($restoredPath, SQLITE3_OPEN_READONLY);

    try {
        $count = $database->querySingle('select count(*) from contacts');
    } finally {
        $database->close();
    }

    expect($count)->toBe(2);
});

test('it does not run rclone when remote backups are skipped', function () {
    Process::fake();
    Process::preventStrayProcesses();
    createBackupTestDatabase($this->databasePath);

    $this->artisan('db:backup', ['--skip-remote' => true])
        ->assertSuccessful();

    Process::assertNothingRan();
});

test('it uploads and prunes remote backups when enabled', function () {
    Process::fake(fn () => Process::result());
    Process::preventStrayProcesses();

    config(['backup.remote.enabled' => true]);
    createBackupTestDatabase($this->databasePath);

    $this->artisan('db:backup', ['--reason' => 'scheduled'])
        ->assertSuccessful();

    Process::assertRan(fn ($process): bool => str_contains(processCommand($process->command), 'rclone copyto')
        && str_contains(processCommand($process->command), 'r2:hirsch-sales-map-backups/database/')
        && str_contains(processCommand($process->command), 'scheduled.sqlite.gz'));

    Process::assertRan(fn ($process): bool => str_contains(processCommand($process->command), 'rclone delete')
        && str_contains(processCommand($process->command), '--min-age 14d'));
});

test('it prunes local backups older than the retention window', function () {
    createBackupTestDatabase($this->databasePath);
    File::ensureDirectoryExists($this->backupPath);

    $oldBackup = $this->backupPath.'/old.sqlite.gz';
    $recentBackup = $this->backupPath.'/recent.sqlite.gz';

    File::put($oldBackup, 'old');
    File::put($recentBackup, 'recent');

    touch($oldBackup, now()->subDays(15)->getTimestamp());
    touch($recentBackup, now()->subDays(2)->getTimestamp());

    $this->artisan('db:backup', ['--skip-remote' => true])
        ->assertSuccessful();

    expect(File::exists($oldBackup))->toBeFalse()
        ->and(File::exists($recentBackup))->toBeTrue();
});

test('it fails clearly when the sqlite database is missing', function () {
    $this->artisan('db:backup', ['--skip-remote' => true])
        ->assertFailed()
        ->expectsOutputToContain('SQLite database not found');
});

test('the scheduled backup runs daily', function () {
    $this->artisan('schedule:list')
        ->expectsOutputToContain('db:backup --reason=scheduled')
        ->assertSuccessful();
});

function createBackupTestDatabase(string $path): void
{
    File::ensureDirectoryExists(dirname($path));

    $database = new SQLite3($path, SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);

    try {
        $database->exec('create table contacts (id integer primary key, name text not null)');
        $database->exec("insert into contacts (name) values ('Taylor'), ('Nuno')");
    } finally {
        $database->close();
    }
}

function processCommand(array|string $command): string
{
    return is_array($command) ? implode(' ', $command) : $command;
}
