<?php

namespace App\Providers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        $this->ensureSqliteDatabasesExist();
    }

    /**
     * Ensure configured SQLite database files exist and use WAL mode.
     */
    protected function ensureSqliteDatabasesExist(): void
    {
        foreach (['sqlite', 'queue_db'] as $connectionName) {
            $database = config("database.connections.{$connectionName}.database");

            if ($database && $database !== ':memory:' && ! file_exists($database)) {
                $dir = dirname($database);
                if (! is_dir($dir)) {
                    @mkdir($dir, 0755, true);
                }
                @touch($database);
                try {
                    DB::connection($connectionName)->statement('PRAGMA journal_mode = wal;');
                } catch (\Throwable) {
                    // Silently continue if database connection is deferred
                }
            }
        }
    }
}
