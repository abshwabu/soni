<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class RestoreDatabaseCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:restore 
                            {file? : Path to the backup file (.sqlite or .sqlite.gz)}
                            {--connection=sqlite : Target database connection}
                            {--force : Run without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Safely restore a SQLite database backup (.sqlite or .sqlite.gz) with pre-restore integrity validation';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $targetConn = $this->option('connection') ?: 'sqlite';
        $activeDbPath = config("database.connections.{$targetConn}.database");

        if (! $activeDbPath || $activeDbPath === ':memory:') {
            $this->error("Connection [{$targetConn}] does not use a persistent file.");
            return self::FAILURE;
        }

        $backupFile = $this->argument('file');

        if (! $backupFile) {
            $backupDir = storage_path('app/backups');
            $files = File::exists($backupDir) ? File::files($backupDir) : [];
            $matchingFiles = array_values(array_filter(
                $files,
                fn ($f) => str_contains($f->getFilename(), $targetConn) && (str_ends_with($f->getFilename(), '.sqlite') || str_ends_with($f->getFilename(), '.sqlite.gz'))
            ));

            if (empty($matchingFiles)) {
                $this->error("No backup files found in [{$backupDir}] for connection [{$targetConn}].");
                return self::FAILURE;
            }

            usort($matchingFiles, fn ($a, $b) => $b->getMTime() <=> $a->getMTime());
            $choices = array_map(fn ($f) => $f->getFilename(), $matchingFiles);
            $selected = $this->choice('Select a backup to restore', $choices, 0);
            $backupFile = "{$backupDir}/{$selected}";
        }

        if (! file_exists($backupFile)) {
            $this->error("Backup file not found at: {$backupFile}");
            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm("⚠️  WARNING: Restoring will overwrite the current [{$targetConn}] database ({$activeDbPath}). Do you wish to continue?")) {
            $this->info('Restoration cancelled.');
            return self::SUCCESS;
        }

        $this->info("🔄 Initiating restoration from [{$backupFile}]...");

        $tempSqliteFile = null;
        if (str_ends_with($backupFile, '.gz')) {
            $this->line("   Decompressing gzip archive...");
            $tempSqliteFile = storage_path('app/backups/temp_restore_' . uniqid() . '.sqlite');
            $this->decompressGzip($backupFile, $tempSqliteFile);
            $candidateFile = $tempSqliteFile;
        } else {
            $candidateFile = $backupFile;
        }

        // Integrity verification on candidate file before replacing active database
        $this->line("   Verifying candidate backup integrity...");
        if (! $this->verifySqliteIntegrity($candidateFile)) {
            $this->error("❌ Integrity check failed on candidate file. Restoration aborted to protect active database!");
            if ($tempSqliteFile && file_exists($tempSqliteFile)) {
                @unlink($tempSqliteFile);
            }
            return self::FAILURE;
        }
        $this->info("   ✅ Integrity verified successfully.");

        // Disconnect and purge PDO connections
        DB::purge($targetConn);

        // Atomic swap
        $this->line("   Replacing active database file...");
        $activeDir = dirname($activeDbPath);
        if (! is_dir($activeDir)) {
            mkdir($activeDir, 0755, true);
        }

        // Backup existing db just in case
        if (file_exists($activeDbPath)) {
            @copy($activeDbPath, "{$activeDbPath}.pre_restore.bak");
        }

        // Copy candidate to active path
        copy($candidateFile, $activeDbPath);

        // Remove old WAL and SHM files so they don't corrupt the newly restored file
        @unlink("{$activeDbPath}-wal");
        @unlink("{$activeDbPath}-shm");

        // Clean up temporary decompressed file
        if ($tempSqliteFile && file_exists($tempSqliteFile)) {
            @unlink($tempSqliteFile);
        }

        // Reconnect and configure WAL mode
        try {
            DB::reconnect($targetConn);
            $check = DB::connection($targetConn)->select('PRAGMA integrity_check;');
            $result = $check[0]->integrity_check ?? 'unknown';

            DB::connection($targetConn)->statement('PRAGMA journal_mode = wal;');
            DB::connection($targetConn)->statement('PRAGMA busy_timeout = 5000;');

            $this->info("🎉 Successfully restored [{$targetConn}]! Integrity check result: {$result}");
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("❌ Post-restore database connection failed: " . $e->getMessage());
            return self::FAILURE;
        }
    }

    /**
     * Decompress a .gz file to target file.
     */
    protected function decompressGzip(string $sourceGz, string $targetFile): void
    {
        $gz = gzopen($sourceGz, 'rb');
        $out = fopen($targetFile, 'wb');

        if (! $gz || ! $out) {
            throw new \RuntimeException("Failed to open files for decompression: {$sourceGz} -> {$targetFile}");
        }

        while (! gzeof($gz)) {
            $chunk = gzread($gz, 1024 * 512);
            fwrite($out, $chunk);
        }

        gzclose($gz);
        fclose($out);
    }

    /**
     * Verify SQLite file integrity.
     */
    protected function verifySqliteIntegrity(string $sqlitePath): bool
    {
        try {
            $pdo = new \PDO("sqlite:{$sqlitePath}");
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $stmt = $pdo->query('PRAGMA integrity_check;');
            $rows = $stmt->fetchAll(\PDO::FETCH_COLUMN);

            return ! empty($rows) && strtolower($rows[0]) === 'ok';
        } catch (\Throwable) {
            return false;
        }
    }
}
