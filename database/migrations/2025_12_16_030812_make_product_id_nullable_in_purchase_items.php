<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // For SQLite, we need to use raw SQL
        if (config('database.default') === 'sqlite') {
            // SQLite doesn't support ALTER COLUMN, so we skip
            return;
        }

        // For MySQL
        DB::statement('ALTER TABLE purchase_items MODIFY product_id BIGINT UNSIGNED NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (config('database.default') === 'sqlite') {
            return;
        }

        DB::statement('ALTER TABLE purchase_items MODIFY product_id BIGINT UNSIGNED NOT NULL');
    }
};
