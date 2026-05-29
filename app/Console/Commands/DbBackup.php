<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class DbBackup extends Command
{
    protected $signature = 'db:backup';
    protected $description = 'Backup database and send email';

    public function handle()
    {
        // Ensure local backup directory exists (storage/app/db-backups)
        $backupDir = storage_path('app/db-backups');
        if (! is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $fileName = 'backup_' . now()->format('Y_m_d_H_i_s') . '.sql';
        $filePath = $backupDir . '/' . $fileName;

        // mysqldump command
        $command = sprintf(
            'mysqldump --user=%s --password=%s --host=%s %s > %s',
            escapeshellarg(env('DB_USERNAME')),
            escapeshellarg(env('DB_PASSWORD')),
            escapeshellarg(env('DB_HOST', '127.0.0.1')),
            escapeshellarg(env('DB_DATABASE')),
            escapeshellarg($filePath)
        );

        exec($command, $output, $result);

        if ($result !== 0) {
            $this->error('❌ Database backup failed.');
            return;
        }

        // Upload backup file to the backup filesystem disk.
        // BACKUP_DISK (if set) takes precedence; otherwise we fall back to FILESYSTEM_DRIVER / filesystems.default.
        $diskName = env('BACKUP_DISK', config('filesystems.default'));
        $disk = Storage::disk($diskName);
        $remotePath = 'db-backups/' . $fileName;
        $disk->put($remotePath, fopen($filePath, 'r'));

        // Try to generate a full URL for the uploaded backup
        $backupUrl = $remotePath;
        try {
            if (method_exists($disk, 'temporaryUrl')) {
                // For disks like S3 that support temporary URLs
                $backupUrl = $disk->temporaryUrl($remotePath, now()->addDay());
            } elseif (method_exists($disk, 'url')) {
                // For disks that expose a public URL (e.g. public, s3 with URL configured)
                $backupUrl = $disk->url($remotePath);
            }
        } catch (\Throwable $e) {
            // If URL generation fails, fall back to the remote path string
            $backupUrl = $remotePath;
        }

        // If using the "public" disk, explicitly build a URL with APP_URL
        if ($diskName === 'public') {
            $appUrl = rtrim(config('app.url') ?? env('APP_URL', ''), '/');
            if (! empty($appUrl)) {
                $backupUrl = $appUrl.'/storage/'.ltrim($remotePath, '/');
            }
        }

        // Prepare HTML email body with backup details
        $createdAt = now()->toDateTimeString();
        $htmlBody = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Database Backup Created</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f5f5f5; padding: 20px; }
        .card { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 6px; box-shadow: 0 2px 4px rgba(0,0,0,0.08); padding: 20px; }
        h1 { font-size: 20px; margin-bottom: 10px; }
        p { margin: 0 0 10px; }
        .meta { margin-top: 15px; }
        .meta table { width: 100%; border-collapse: collapse; }
        .meta th, .meta td { text-align: left; padding: 6px 8px; font-size: 13px; }
        .meta th { background-color: #f0f0f0; }
        .footer { margin-top: 20px; font-size: 12px; color: #777; }
        code { font-family: monospace; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Database Backup Created Successfully</h1>
        <p>The latest database backup has been created on the server.</p>

        <div class="meta">
            <table>
                <tr>
                    <th>Created At</th>
                    <td>{$createdAt}</td>
                </tr>
                <tr>
                    <th>Backup File</th>
                    <td>{$fileName}</td>
                </tr>
                <tr>
                    <th>Local Path</th>
                    <td><code>{$filePath}</code></td>
                </tr>
                <tr>
                    <th>Storage Disk</th>
                    <td><code>{$diskName}</code></td>
                </tr>
                <tr>
                    <th>Download Link</th>
                    <td><a href="{$backupUrl}">Download backup</a></td>
                </tr>
            </table>
        </div>

        <p>You can download this file from the above link or copy it directly from the server at the local path.</p>

        <div class="footer">
            <p>This is an automated message from the database backup job.</p>
        </div>
    </div>
</body>
</html>
HTML;

        // Resolve recipient email addresses from env (comma-separated)
        $recipientsEnv = env('DB_BACKUP_EMAILS', 'team@taldartechconsultancy.com');
        $recipients = array_filter(array_map('trim', explode(',', $recipientsEnv)));

        // Send HTML email (no attachment, backup kept on backup disk)
        Mail::send([], [], function ($message) use ($htmlBody, $recipients) {
            if (count($recipients) > 0) {
                $message->to($recipients);
            }

            $message->subject('Database Backup - ' . now()->toDateTimeString())
                    ->html($htmlBody);
        });
        // Clean up local temporary dump files – backups are kept only on the backup disk
        foreach (glob($backupDir.'/backup_*.sql') ?: [] as $tempFile) {
            @unlink($tempFile);
        }

        // ...and on the configured backup disk
        try {
            $remoteBackups = $disk->files('db-backups');
            // Filter to .sql backup files only
            $remoteBackups = array_values(array_filter($remoteBackups, function ($path) {
                return str_ends_with($path, '.sql');
            }));

            rsort($remoteBackups);

            foreach (array_slice($remoteBackups, 4) as $oldRemote) {
                $disk->delete($oldRemote);
            }
        } catch (\Throwable $e) {
            // If remote cleanup fails, avoid breaking the command; backups are still created.
        }

        $this->info('✅ Backup created locally & HTML email sent successfully.');
    }
}