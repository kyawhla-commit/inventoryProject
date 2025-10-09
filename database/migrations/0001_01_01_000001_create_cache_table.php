<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cache', function (Blueprint $table) {
            $table->string('key', 191)->primary(); // Explicit length
            $table->mediumText('value');
            $table->integer('expiration')->index(); // Single index for cleanup
            
            // Remove duplicate index - expiration is already indexed above
            // $table->index(['expiration', 'key']); // This creates duplicate indexing
        });

        Schema::create('cache_locks', function (Blueprint $table) {
            $table->string('key', 191)->primary();
            $table->string('owner', 191);
            $table->integer('expiration')->index();
            
            // Single composite index is sufficient
            $table->index(['key', 'expiration']);
        });

        // Apply safe optimizations
        $this->optimizeTables();
    }

    /**
     * Apply database-specific optimizations safely
     */
    protected function optimizeTables(): void
    {
        $driver = DB::connection()->getDriverName();
        
        try {
            if ($driver === 'mysql') {
                // Safe MySQL optimizations
                DB::statement('ALTER TABLE cache ENGINE=InnoDB ROW_FORMAT=DYNAMIC;');
                DB::statement('ALTER TABLE cache_locks ENGINE=InnoDB ROW_FORMAT=DYNAMIC;');
                
                // Remove problematic KEY_BLOCK_SIZE and buffer pool settings
            }

            if ($driver === 'pgsql') {
                // Safe PostgreSQL optimizations
                DB::statement('ALTER TABLE cache SET (fillfactor = 90);');
                DB::statement('ALTER TABLE cache_locks SET (fillfactor = 90);');
                
                // Remove aggressive autovacuum settings that might cause performance issues
            }

            if ($driver === 'sqlite') {
                DB::statement('PRAGMA optimize;');
            }
        } catch (\Exception $e) {
            // Log error but don't break migration
            \Log::warning('Cache table optimization failed: ' . $e->getMessage());
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cache_locks');
        Schema::dropIfExists('cache');
    }
};