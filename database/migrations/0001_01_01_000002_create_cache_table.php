<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The database connection that should be used by the migration.
     *
     * @var string
     */
    protected $connection = 'queue_db';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::connection('queue_db')->dropIfExists('cache_locks');
        Schema::connection('queue_db')->dropIfExists('cache');

        Schema::connection('queue_db')->create('cache', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->mediumText('value');
            $table->integer('expiration');
        });

        Schema::connection('queue_db')->create('cache_locks', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->string('owner');
            $table->integer('expiration');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('queue_db')->dropIfExists('cache_locks');
        Schema::connection('queue_db')->dropIfExists('cache');
    }
};
