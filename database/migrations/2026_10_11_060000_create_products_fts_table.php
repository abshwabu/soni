<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        try {
            DB::statement("CREATE VIRTUAL TABLE IF NOT EXISTS products_fts USING fts5(
                product_id UNINDEXED,
                name,
                brand_name,
                notes,
                scent_family,
                description
            )");
        } catch (\Throwable $e) {
            // FTS5 might not be available in some environments
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            DB::statement("DROP TABLE IF EXISTS products_fts");
        } catch (\Throwable $e) {
            // Ignore
        }
    }
};
