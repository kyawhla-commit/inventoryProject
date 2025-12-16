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
        Schema::table('stock_movements', function (Blueprint $table) {
            if (!Schema::hasColumn('stock_movements', 'product_id')) {
                $table->unsignedBigInteger('product_id')->nullable()->after('id');
            }
            if (!Schema::hasColumn('stock_movements', 'raw_material_id')) {
                $table->unsignedBigInteger('raw_material_id')->nullable()->after('product_id');
            }
            if (!Schema::hasColumn('stock_movements', 'type')) {
                $table->string('type', 50)->default('adjustment')->after('raw_material_id');
            }
            if (!Schema::hasColumn('stock_movements', 'quantity')) {
                $table->decimal('quantity', 12, 2)->default(0)->after('type');
            }
            if (!Schema::hasColumn('stock_movements', 'unit_price')) {
                $table->decimal('unit_price', 12, 2)->nullable()->after('quantity');
            }
            if (!Schema::hasColumn('stock_movements', 'reference_type')) {
                $table->string('reference_type')->nullable()->after('unit_price');
            }
            if (!Schema::hasColumn('stock_movements', 'reference_id')) {
                $table->unsignedBigInteger('reference_id')->nullable()->after('reference_type');
            }
            if (!Schema::hasColumn('stock_movements', 'notes')) {
                $table->text('notes')->nullable()->after('reference_id');
            }
            if (!Schema::hasColumn('stock_movements', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('notes');
            }
            if (!Schema::hasColumn('stock_movements', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('user_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $columns = ['product_id', 'raw_material_id', 'type', 'quantity', 'unit_price', 
                       'reference_type', 'reference_id', 'notes', 'user_id', 'created_by'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('stock_movements', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
