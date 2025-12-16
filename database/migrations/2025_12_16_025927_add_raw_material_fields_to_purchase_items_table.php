<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('purchase_items', function (Blueprint $table) {
            // Add raw_material_id column if it doesn't exist
            if (!Schema::hasColumn('purchase_items', 'raw_material_id')) {
                $table->unsignedBigInteger('raw_material_id')->nullable()->after('product_id');
            }
            
            // Add unit_price column if it doesn't exist
            if (!Schema::hasColumn('purchase_items', 'unit_price')) {
                $table->decimal('unit_price', 12, 2)->default(0)->after('quantity');
            }
            
            // Add total_amount column if it doesn't exist
            if (!Schema::hasColumn('purchase_items', 'total_amount')) {
                $table->decimal('total_amount', 12, 2)->default(0)->after('unit_price');
            }
            
            // Add unit column if it doesn't exist
            if (!Schema::hasColumn('purchase_items', 'unit')) {
                $table->string('unit', 50)->nullable()->after('total_amount');
            }
            
            // Add notes column if it doesn't exist
            if (!Schema::hasColumn('purchase_items', 'notes')) {
                $table->text('notes')->nullable()->after('unit');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_items', function (Blueprint $table) {
            $columns = ['raw_material_id', 'unit_price', 'total_amount', 'unit', 'notes'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('purchase_items', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
