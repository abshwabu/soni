<?php

namespace App\Providers;

use Illuminate\Console\Events\CommandStarting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
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
        $this->handleDatabaseWipeEvents();

        // Performance: Prevent N+1 lazy loading outside production
        \Illuminate\Database\Eloquent\Model::preventLazyLoading(! $this->app->isProduction() && ! $this->app->runningUnitTests());

        // Performance: Slow query monitoring in non-production
        if (! $this->app->isProduction()) {
            DB::listen(function ($query) {
                if ($query->time > 50) {
                    \Illuminate\Support\Facades\Log::warning("Slow database query detected ({$query->time}ms) on [{$query->connectionName}]: {$query->sql}");
                }
            });
        }

        // Cache invalidation on model changes
        $clearHome = fn () => \Illuminate\Support\Facades\Cache::forget('home_page_data');
        $clearNav = function () {
            \Illuminate\Support\Facades\Cache::forget('nav_categories');
            \Illuminate\Support\Facades\Cache::forget('nav_brands');
            \Illuminate\Support\Facades\Cache::forget('home_page_data');
        };
        $clearSettings = function () {
            \Illuminate\Support\Facades\Cache::forget('store_settings');
            \Illuminate\Support\Facades\Cache::forget('home_page_data');
        };

        \App\Models\Product::saved($clearHome);
        \App\Models\Product::deleted($clearHome);
        \App\Models\Category::saved($clearNav);
        \App\Models\Category::deleted($clearNav);
        \App\Models\Brand::saved($clearNav);
        \App\Models\Brand::deleted($clearNav);
        \App\Models\Setting::saved($clearSettings);
        \App\Models\Setting::deleted($clearSettings);
        \App\Models\Banner::saved($clearHome);
        \App\Models\Banner::deleted($clearHome);
        \App\Models\Testimonial::saved($clearHome);
        \App\Models\Testimonial::deleted($clearHome);

        Event::listen(
            \App\Events\OrderPlaced::class,
            \App\Listeners\SendOrderNotificationToTelegram::class
        );
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

    /**
     * Cleanly wipe secondary and primary SQLite databases during migrate:fresh or db:wipe.
     */
    protected function handleDatabaseWipeEvents(): void
    {
        Event::listen(CommandStarting::class, function (CommandStarting $event) {
            if (in_array($event->command, ['migrate:fresh', 'db:wipe'])) {
                DB::purge('sqlite');
                DB::purge('queue_db');

                foreach (['sqlite', 'queue_db'] as $conn) {
                    $path = config("database.connections.{$conn}.database");
                    if ($path && file_exists($path)) {
                        @unlink($path);
                        @unlink("{$path}-wal");
                        @unlink("{$path}-shm");
                        @touch($path);
                    }
                }
            }
        });
    }
}
