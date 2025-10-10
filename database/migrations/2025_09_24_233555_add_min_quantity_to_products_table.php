<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Add min_quantity as an alias to minimum_stock_level for API compatibility
            $table->integer('min_quantity')->default(0)->after('minimum_stock_level');
        });

        // Copy values from minimum_stock_level to min_quantity
        DB::statement('UPDATE products SET min_quantity = minimum_stock_level');
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('min_quantity');
        });
    }
};
