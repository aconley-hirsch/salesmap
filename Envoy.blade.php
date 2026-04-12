@setup
    $server = $server ?? 'deploy@72.14.177.45';
    $path = $path ?? '/var/www/hirsch-sales-map';
    $branch = $branch ?? 'main';
    $php = $php ?? 'php8.4';
    $phpFpm = $phpFpm ?? 'php8.4-fpm';
@endsetup

@servers(['web' => $server])

@story('deploy')
    pull
    composer
    assets
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

@task('logs', ['on' => 'web'])
    echo "Last 50 lines of application log..."
    cd {{ $path }}
    tail -50 storage/logs/laravel.log
@endtask
