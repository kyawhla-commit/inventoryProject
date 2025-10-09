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
        Schema::create('inventory_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_number')->unique();
            $table->enum('type', ['in', 'out', 'adjustment']);
            $table->enum('source', ['sale', 'purchase', 'production', 'adjustment', 'return', 'transfer', 'waste']);
            
            // Item references (one of these will be filled)
            $table->foreignId('product_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('raw_material_id')->nullable()->constrained()->onDelete('cascade');
            
            // Transaction details
            $table->decimal('quantity', 10, 3);
            $table->decimal('unit_cost', 10, 2)->nullable();
            $table->decimal('total_cost', 10, 2)->nullable();
            $table->string('unit', 50);
            
            // Stock levels
            $table->decimal('stock_before', 10, 3);
            $table->decimal('stock_after', 10, 3);
            
            // References to source documents
            $table->foreignId('sale_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('purchase_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('production_plan_item_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('order_id')->nullable()->constrained()->onDelete('set null');
            
            // Adjustment details
            $table->string('reason')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            
            // Audit trail
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            
            // FIX: Add default value to transaction_date
            $table->timestamp('transaction_date')->useCurrent();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['product_id', 'transaction_date']);
            $table->index(['raw_material_id', 'transaction_date']);
            $table->index(['type', 'source']);
            $table->index('transaction_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_transactions');
    }
};