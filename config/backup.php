<?php

return [
    'sqlite_path' => env('BACKUP_SQLITE_PATH'),

    'local_path' => env('BACKUP_LOCAL_PATH', storage_path('app/backups/database')),

    'retention_days' => (int) env('BACKUP_RETENTION_DAYS', 14),

    'remote' => [
        'enabled' => filter_var(env('BACKUP_REMOTE_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
        'rclone_remote' => env('BACKUP_RCLONE_REMOTE', 'r2:hirsch-sales-map-backups'),
    ],
];
