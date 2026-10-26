<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;
use Throwable;

class BackupDatabaseToS3 extends Command
{
    protected $signature = 'database:backup-to-s3';

    protected $description = 'Backup the database to S3 and remove backups older than the retention window';

    public function handle(): int
    {
        $disk = (string) config('database_backup.disk', 's3');
        $pathPrefix = trim((string) config('database_backup.path_prefix', 'database-backups'), '/');
        $retentionHours = max(1, (int) config('database_backup.retention_hours', 24));
        $localFilePath = null;

        try {
            $backupFileName = 'db-backup-'.now()->format('Y-m-d_H-i-s').'.sql';
            $localDirectory = storage_path('app/private/tmp-backups');
            $localFilePath = $localDirectory.DIRECTORY_SEPARATOR.$backupFileName;
            $remotePath = $pathPrefix !== '' ? $pathPrefix.'/'.$backupFileName : $backupFileName;

            File::ensureDirectoryExists($localDirectory);

            $this->createDatabaseDump($localFilePath);

            Storage::disk($disk)->putFileAs(
                $pathPrefix !== '' ? $pathPrefix : '',
                new \Illuminate\Http\File($localFilePath),
                $backupFileName
            );

            $deletedCount = $this->pruneOldBackups($disk, $pathPrefix, $retentionHours);

            $this->info(sprintf(
                'Database backup uploaded to [%s:%s]. Removed %d backup(s) older than %d hour(s).',
                $disk,
                $remotePath,
                $deletedCount,
                $retentionHours
            ));

            return self::SUCCESS;
        } catch (Throwable $throwable) {
            Log::error('Failed to back up database to S3.', [
                'disk' => $disk,
                'path_prefix' => $pathPrefix,
                'message' => $throwable->getMessage(),
            ]);

            $this->error('Database backup failed. Check logs for details.');

            return self::FAILURE;
        } finally {
            if (is_string($localFilePath) && $localFilePath !== '') {
                File::delete($localFilePath);
            }
        }
    }

    private function createDatabaseDump(string $localFilePath): void
    {
        $defaultConnection = (string) config('database.default');
        $connectionConfig = (array) config('database.connections.'.$defaultConnection, []);

        if (($connectionConfig['driver'] ?? null) !== 'mysql') {
            throw new \RuntimeException('Only MySQL connections are supported for database backups.');
        }

        $databaseName = (string) ($connectionConfig['database'] ?? '');
        $username = (string) ($connectionConfig['username'] ?? '');
        $password = (string) ($connectionConfig['password'] ?? '');
        $host = (string) ($connectionConfig['host'] ?? '127.0.0.1');
        $port = (string) ($connectionConfig['port'] ?? '3306');

        if ($databaseName === '' || $username === '') {
            throw new \RuntimeException('Database configuration is incomplete for backup generation.');
        }

        $command = [
            'mysqldump',
            '--single-transaction',
            '--quick',
            '--skip-lock-tables',
            '--host='.$host,
            '--port='.$port,
            '--user='.$username,
            '--result-file='.$localFilePath,
            $databaseName,
        ];

        $previousMysqlPassword = getenv('MYSQL_PWD');
        putenv('MYSQL_PWD='.$password);

        try {
            // Inherit the process environment (including PATH) so tests can inject a fake mysqldump.
            $process = new Process($command);
            $process->setTimeout(300);
            $process->run();
        } finally {
            if ($previousMysqlPassword === false) {
                putenv('MYSQL_PWD');
            } else {
                putenv('MYSQL_PWD='.$previousMysqlPassword);
            }
        }

        if (! $process->isSuccessful()) {
            throw new \RuntimeException('Database dump failed: '.$process->getErrorOutput());
        }
    }

    private function pruneOldBackups(string $disk, string $pathPrefix, int $retentionHours): int
    {
        $cutoffTimestamp = now()->subHours($retentionHours)->getTimestamp();
        $directory = $pathPrefix !== '' ? $pathPrefix : '';
        $storage = Storage::disk($disk);
        $deletedCount = 0;

        foreach ($storage->files($directory) as $remotePath) {
            if (! str_ends_with($remotePath, '.sql')) {
                continue;
            }

            $lastModified = $storage->lastModified($remotePath);
            if ($lastModified >= $cutoffTimestamp) {
                continue;
            }

            $storage->delete($remotePath);
            $deletedCount++;
        }

        return $deletedCount;
    }
}
