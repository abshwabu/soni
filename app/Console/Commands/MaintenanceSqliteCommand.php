<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MaintenanceSqliteCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:maintenance-sqlite';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Perform weekly SQLite integrity check, WAL journal truncation, and query planner optimization';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🛠️  Running SQLite maintenance routine...');

        $connections = ['sqlite', 'queue_db'];
        $rows = [];

        foreach ($connections as $conn) {
            $dbPath = config("database.connections.{$conn}.database");

            if (! $dbPath || $dbPath === ':memory:' || ! file_exists($dbPath)) {
                $rows[] = [$conn, 'N/A', 'Skipped (In-memory/Missing)', 'N/A', 'N/A'];
                continue;
            }

            $walPath = "{$dbPath}-wal";
            $walBeforeSize = file_exists($walPath) ? filesize($walPath) : 0;
            $dbBeforeSize = filesize($dbPath);

            try {
                // 1. PRAGMA integrity_check
                $integrity = DB::connection($conn)->select('PRAGMA integrity_check;');
                $integrityResult = $integrity[0]->integrity_check ?? 'unknown';

                // 2. PRAGMA wal_checkpoint(TRUNCATE)
                // Flushes all WAL pages back to database and truncates WAL to 0 bytes
                $checkpoint = DB::connection($conn)->select('PRAGMA wal_checkpoint(TRUNCATE);');
                $checkpointStatus = ! empty($checkpoint) ? 'Truncated' : 'Executed';

                // 3. PRAGMA optimize
                DB::connection($conn)->statement('PRAGMA optimize;');

                // Clear PHP file stat cache to get accurate post-checkpoint size
                clearstatcache(true, $walPath);
                $walAfterSize = file_exists($walPath) ? filesize($walPath) : 0;

                $rows[] = [
                    $conn,
                    $integrityResult === 'ok' ? '✅ OK' : "⚠️ {$integrityResult}",
                    $checkpointStatus,
                    $this->formatBytes($dbBeforeSize),
                    "{$this->formatBytes($walBeforeSize)} → {$this->formatBytes($walAfterSize)}",
                ];
            } catch (\Throwable $e) {
                $rows[] = [$conn, '❌ Failed', $e->getMessage(), 'N/A', 'N/A'];
            }
        }

        $this->table(
            ['Connection', 'Integrity Check', 'WAL Checkpoint', 'DB Size', 'WAL Size (Before → After)'],
            $rows
        );

        $this->info('✨ SQLite maintenance completed successfully.');

        return self::SUCCESS;
    }

    /**
     * Format bytes into human-readable string.
     */
    protected function formatBytes(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' KB';
        }
        return "{$bytes} B";
    }
}
