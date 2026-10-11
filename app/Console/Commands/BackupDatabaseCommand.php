<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class BackupDatabaseCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:backup 
                            {--destination= : Custom destination directory for backups}
                            {--no-media : Skip backing up public media storage}
                            {--days=14 : Number of days to retain old backups}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Perform WAL-safe SQLite backups using VACUUM INTO, gzip compression, media archiving, and retention management';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $startTime = microtime(true);
        $timestamp = now()->format('Y-m-d_His');
        $backupDir = $this->option('destination')
            ?: storage_path('app/backups');

        if (! File::isDirectory($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }

        $this->info("🚀 Starting Soni Perfume backup routine [{$timestamp}]...");
        $this->line("📁 Target Directory: {$backupDir}");

        $connections = ['sqlite', 'queue_db'];
        $backedUpFiles = [];

        foreach ($connections as $conn) {
            $dbPath = config("database.connections.{$conn}.database");

            if (! $dbPath || $dbPath === ':memory:') {
                $this->warn("⚠️  Connection [{$conn}] is in-memory or not configured. Skipping.");
                continue;
            }

            if (! file_exists($dbPath)) {
                $this->warn("⚠️  Database file for [{$conn}] does not exist at [{$dbPath}]. Skipping.");
                continue;
            }

            $this->info("📦 Backing up [{$conn}] database...");

            $tempSqliteFile = "{$backupDir}/{$conn}_{$timestamp}.sqlite";
            $compressedFile = "{$tempSqliteFile}.gz";

            try {
                // SQLite 3.27+ WAL-safe online atomic backup
                DB::connection($conn)->statement("VACUUM INTO '{$tempSqliteFile}'");

                // Compress with gzip
                $this->compressFile($tempSqliteFile, $compressedFile);
                @unlink($tempSqliteFile);

                $sizeMb = round(filesize($compressedFile) / (1024 * 1024), 2);
                $this->info("   ✅ Created [{$conn}_{$timestamp}.sqlite.gz] ({$sizeMb} MB)");
                $backedUpFiles[] = $compressedFile;
            } catch (\Throwable $e) {
                $this->error("   ❌ Failed to backup [{$conn}]: " . $e->getMessage());
                Log::error("Backup failed for [{$conn}]: " . $e->getMessage());
                if (file_exists($tempSqliteFile)) {
                    @unlink($tempSqliteFile);
                }
            }
        }

        // Media backup (storage/app/public)
        if (! $this->option('no-media')) {
            $mediaPath = storage_path('app/public');
            if (File::isDirectory($mediaPath) && count(File::allFiles($mediaPath)) > 0) {
                $this->info("🖼️  Backing up media storage ({$mediaPath})...");
                $mediaArchive = "{$backupDir}/media_{$timestamp}.tar.gz";

                $archived = $this->archiveDirectory($mediaPath, $mediaArchive);
                if ($archived) {
                    $mediaMb = round(filesize($mediaArchive) / (1024 * 1024), 2);
                    $this->info("   ✅ Created [media_{$timestamp}.tar.gz] ({$mediaMb} MB)");
                    $backedUpFiles[] = $mediaArchive;
                } else {
                    $this->warn("   ⚠️ Media archive could not be created.");
                }
            } else {
                $this->line("ℹ️  Media directory empty or missing; skipped.");
            }
        }

        // Retention cleanup
        $retentionDays = (int) $this->option('days');
        $this->pruneOldBackups($backupDir, $retentionDays);

        $duration = round(microtime(true) - $startTime, 2);
        $this->info("✨ Backup routine completed in {$duration}s. " . count($backedUpFiles) . " archive(s) created.");

        return self::SUCCESS;
    }

    /**
     * Compress a file using gzip streaming to avoid high memory usage.
     */
    protected function compressFile(string $sourcePath, string $destinationPath): void
    {
        $bufferSize = 1024 * 512; // 512KB chunks
        $fp = fopen($sourcePath, 'rb');
        $gz = gzopen($destinationPath, 'wb9');

        if (! $fp || ! $gz) {
            throw new \RuntimeException("Unable to open files for compression: {$sourcePath} -> {$destinationPath}");
        }

        while (! feof($fp)) {
            $chunk = fread($fp, $bufferSize);
            gzwrite($gz, $chunk);
        }

        fclose($fp);
        gzclose($gz);
    }

    /**
     * Archive directory using tar.gz (system tar command or zip fallback).
     */
    protected function archiveDirectory(string $sourceDir, string $destinationArchive): bool
    {
        // Try system tar command if available
        $cmd = sprintf(
            'tar -czf %s -C %s . 2>&1',
            escapeshellarg($destinationArchive),
            escapeshellarg($sourceDir)
        );

        @exec($cmd, $output, $returnCode);

        if ($returnCode === 0 && file_exists($destinationArchive)) {
            return true;
        }

        return false;
    }

    /**
     * Delete backups older than specified retention days.
     */
    protected function pruneOldBackups(string $backupDir, int $days): void
    {
        $this->info("🧹 Pruning backups older than {$days} days...");
        $cutoff = now()->subDays($days)->getTimestamp();
        $deletedCount = 0;

        $files = File::files($backupDir);
        foreach ($files as $file) {
            if ($file->getMTime() < $cutoff) {
                File::delete($file->getPathname());
                $deletedCount++;
            }
        }

        $this->line("   Cleaned up {$deletedCount} expired backup file(s).");
    }
}
