<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('db:optimize-sqlite', function () {
    $this->info('Running PRAGMA optimize on SQLite databases...');
    try {
        \Illuminate\Support\Facades\DB::statement('PRAGMA optimize;');
        \Illuminate\Support\Facades\DB::connection('queue_db')->statement('PRAGMA optimize;');
        $this->info('✅ SQLite PRAGMA optimize executed successfully.');
    } catch (\Throwable $e) {
        $this->error('Failed to run PRAGMA optimize: ' . $e->getMessage());
    }
})->purpose('Run SQLite PRAGMA optimize to update query planner statistics');

\Illuminate\Support\Facades\Schedule::command('db:optimize-sqlite')->dailyAt('02:00');
\Illuminate\Support\Facades\Schedule::command('sitemap:generate')->dailyAt('03:00');

