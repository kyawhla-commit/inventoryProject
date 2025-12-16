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
        Schema::create('deliveries', function (Blueprint $table) {
            $table->id();
            $table->string('delivery_number')->unique();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            
            // Delivery details
            $table->string('status')->default('pending'); // pending, assigned, picked_up, in_transit, delivered, failed, cancelled
            $table->text('delivery_address')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('contact_name')->nullable();
            
            // Driver/Vehicle info
            $table->string('driver_name')->nullable();
            $table->string('driver_phone')->nullable();
            $table->string('vehicle_number')->nullable();
            
            // Dates
            $table->date('scheduled_date')->nullable();
            $table->time('scheduled_time')->nullable();
            $table->timestamp('picked_up_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            
            // Costs
            $table->decimal('delivery_fee', 12, 2)->default(0);
            $table->decimal('actual_cost', 12, 2)->nullable();
            
            // Notes and tracking
            $table->text('notes')->nullable();
            $table->text('delivery_notes')->nullable(); // Notes from driver
            $table->string('proof_of_delivery')->nullable(); // Image path
            $table->string('recipient_name')->nullable(); // Who received
            
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Delivery status history for tracking
        Schema::create('delivery_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_id')->constrained()->onDelete('cascade');
            $table->string('status');
            $table->text('notes')->nullable();
            $table->string('location')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_status_histories');
        Schema::dropIfExists('deliveries');
    }
};
