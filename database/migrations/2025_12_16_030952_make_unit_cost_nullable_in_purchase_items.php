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
        if (config('database.default') === 'sqlite') {
            return;
        }

        // Make unit_cost nullable with default 0
        DB::statement('ALTER TABLE purchase_items MODIFY unit_cost DECIMAL(8,2) NULL DEFAULT 0');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (config('database.default') === 'sqlite') {
            return;
        }

        DB::statement('ALTER TABLE purchase_items MODIFY unit_cost DECIMAL(8,2) NOT NULL');
    }
};
