@setup
    $server = $server ?? 'deploy@72.14.177.45';
    $path = $path ?? '/var/www/hirsch-sales-map';
    $branch = $branch ?? 'main';
    $php = $php ?? 'php8.4';
    $phpFpm = $phpFpm ?? 'php8.4-fpm';
    $backup = $backup ?? '';
    $rcloneRemote = $rcloneRemote ?? 'r2:hirsch-sales-map-backups';
@endsetup

@servers(['web' => $server])

@story('deploy')
    pull
    composer
    assets
    clear
    backup
    migrate
    optimize
    reload
@endstory

@task('pull', ['on' => 'web'])
    echo "Pulling latest changes from {{ $branch }}..."
    cd {{ $path }}
    git pull origin {{ $branch }}
@endtask

@task('composer', ['on' => 'web'])
    echo "Installing Composer dependencies..."
    cd {{ $path }}
    composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
@endtask

@task('assets', ['on' => 'web'])
    echo "Building frontend assets..."
    cd {{ $path }}
    npm ci
    npm run build
@endtask

@task('clear', ['on' => 'web'])
    echo "Clearing Laravel caches before deployment tasks..."
    cd {{ $path }}
    {{ $php }} artisan optimize:clear
@endtask

@task('backup', ['on' => 'web'])
    echo "Backing up database before deployment..."
    cd {{ $path }}
    {{ $php }} artisan db:backup --reason=deploy
@endtask

@task('migrate', ['on' => 'web'])
    echo "Running migrations..."
    cd {{ $path }}
    {{ $php }} artisan migrate --force
@endtask

@task('optimize', ['on' => 'web'])
    echo "Caching configuration, routes, and views..."
    cd {{ $path }}
    {{ $php }} artisan optimize
@endtask

@task('reload', ['on' => 'web'])
    echo "Reloading PHP-FPM..."
    sudo systemctl reload {{ $phpFpm }}
@endtask

@task('rollback', ['on' => 'web'])
    echo "Rolling back to previous commit..."
    cd {{ $path }}
    git revert --no-edit HEAD
    composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
    {{ $php }} artisan migrate --force
    {{ $php }} artisan optimize
    sudo systemctl reload {{ $phpFpm }}
    echo "Rollback complete."
@endtask

@task('health', ['on' => 'web'])
    echo "Checking application health..."
    cd {{ $path }}
    {{ $php }} artisan about --json | head -1 > /dev/null 2>&1 && echo "Artisan: OK" || echo "Artisan: FAIL"
    curl -sf -o /dev/null -w "HTTP Status: %{http_code}\n" http://localhost || echo "HTTP check: FAIL"
    echo "Disk usage:"
    df -h {{ $path }} | tail -1
    echo "SQLite database size:"
    ls -lh {{ $path }}/database/database.sqlite 2>/dev/null || echo "No database file found"
@endtask

@task('backup-health', ['on' => 'web'])
    echo "Checking backup prerequisites..."
    cd {{ $path }}
    test -f database/database.sqlite && echo "SQLite database: OK" || (echo "SQLite database: FAIL" && exit 1)
    command -v sqlite3 >/dev/null 2>&1 && echo "sqlite3 CLI: OK" || echo "sqlite3 CLI: WARN (Laravel uses PHP SQLite3 for backups)"
    command -v rclone >/dev/null 2>&1 && echo "rclone: OK" || (echo "rclone: FAIL" && exit 1)
    rclone listremotes | grep -q '^r2:$' && echo "rclone remote r2: OK" || (echo "rclone remote r2: FAIL" && exit 1)
@endtask

@task('backup-list', ['on' => 'web'])
    echo "Local backups:"
    cd {{ $path }}
    ls -lh storage/app/backups/database/*.sqlite.gz 2>/dev/null || echo "No local backups found."
    echo "Remote backups:"
    rclone lsf {{ $rcloneRemote }}/database 2>/dev/null || echo "No remote backups found or rclone is not configured."
@endtask

@task('restore-local', ['on' => 'web'])
    set -e

    if [ -z "{{ $backup }}" ]; then
        echo "Pass a backup filename with --backup=YYYY-MM-DD-HHMMSS-reason.sqlite.gz"
        exit 1
    fi

    cd {{ $path }}

    if [ ! -f "storage/app/backups/database/{{ $backup }}" ]; then
        echo "Backup not found: storage/app/backups/database/{{ $backup }}"
        exit 1
    fi

    echo "Restoring local database backup {{ $backup }}..."
    gzip -t "storage/app/backups/database/{{ $backup }}"
    gzip -dc "storage/app/backups/database/{{ $backup }}" > database/database.sqlite.restore
    {{ $php }} artisan down
    cp database/database.sqlite "database/database.sqlite.before-restore-$(date +%Y%m%d%H%M%S)"
    mv database/database.sqlite.restore database/database.sqlite
    {{ $php }} artisan optimize
    sudo systemctl reload {{ $phpFpm }}
    {{ $php }} artisan up
    echo "Restore complete."
@endtask

@task('logs', ['on' => 'web'])
    echo "Last 50 lines of application log..."
    cd {{ $path }}
    tail -50 storage/logs/laravel.log
@endtask
