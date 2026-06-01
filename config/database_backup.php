<?php

return [
    'disk' => env('DB_BACKUP_DISK', 's3'),
    'path_prefix' => env('DB_BACKUP_PATH_PREFIX', 'database-backups'),
    'retention_hours' => (int) env('DB_BACKUP_RETENTION_HOURS', 24),
];
