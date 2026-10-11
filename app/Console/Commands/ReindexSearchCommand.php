<?php

namespace App\Console\Commands;

use App\Services\SearchService;
use Illuminate\Console\Command;

class ReindexSearchCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'search:reindex';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reindex all active products into the SQLite FTS5 search table';

    /**
     * Execute the console command.
     */
    public function handle(SearchService $searchService): int
    {
        $this->info('Starting SQLite FTS5 search re-indexing...');
        $start = microtime(true);

        $count = $searchService->reindexAll();

        $duration = round(microtime(true) - $start, 3);
        $this->info("Successfully indexed {$count} products in {$duration}s.");

        return Command::SUCCESS;
    }
}
