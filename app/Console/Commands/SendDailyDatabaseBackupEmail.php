<?php

namespace App\Console\Commands;

use App\Mail\DailyDatabaseBackupMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;
use Throwable;

class SendDailyDatabaseBackupEmail extends Command
{
    protected $signature = 'backup:email-daily-database';

    protected $description = 'Send the latest daily database backup to configured email recipients';

    public function handle(): int
    {
        $disk = (string) config('database_backup.disk', 'local');
        $pathPrefix = trim((string) config('database_backup.path_prefix', 'database-backups'), '/');
        $recipientList = (string) config('database_backup.email_recipients', '');
        $localFilePath = null;
        $backupPath = null;

        $recipients = collect(explode(',', $recipientList))
            ->map(static fn (string $email): string => trim($email))
            ->filter(static fn (string $email): bool => $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) !== false)
            ->values()
            ->all();

        if ($recipients === []) {
            $this->error('No valid recipients found in DB_BACKUP_EMAIL_RECIPIENTS.');

            return self::FAILURE;
        }

        try {
            $backupFileName = 'db-backup-'.now()->format('Y-m-d_H-i-s').'.sql';
            $localDirectory = storage_path('app/private/tmp-backups');
            $localFilePath = $localDirectory.DIRECTORY_SEPARATOR.$backupFileName;
            $backupPath = $pathPrefix !== '' ? $pathPrefix.'/'.$backupFileName : $backupFileName;

            File::ensureDirectoryExists($localDirectory);

            $this->createDatabaseDump($localFilePath);

            $stream = fopen($localFilePath, 'rb');
            if ($stream === false) {
                throw new \RuntimeException('Unable to open local backup file stream.');
            }

            Storage::disk($disk)->put($backupPath, $stream);
            fclose($stream);

            Mail::to($recipients)->send(new DailyDatabaseBackupMail($disk, $backupPath));

            $this->info(sprintf(
                'Database backup email sent to [%s] with attachment [%s].',
                implode(', ', $recipients),
                $backupPath
            ));

            return self::SUCCESS;
        } catch (Throwable $throwable) {
            Log::error('Failed to send daily database backup email.', [
                'disk' => $disk,
                'path_prefix' => $pathPrefix,
                'message' => $throwable->getMessage(),
            ]);

            $this->error('Failed to send backup email. Check logs for details.');

            return self::FAILURE;
        } finally {
            if (is_string($backupPath) && $backupPath !== '') {
                Storage::disk($disk)->delete($backupPath);
            }

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
            throw new \RuntimeException('Only MySQL connections are currently supported for backup email command.');
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

        $process = new Process($command, null, [
            'MYSQL_PWD' => $password,
        ]);
        $process->setTimeout(300);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new \RuntimeException('Database dump failed: '.$process->getErrorOutput());
        }
    }
}
