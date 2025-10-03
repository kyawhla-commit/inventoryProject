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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->date('order_date');
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->change();
            $table->string('status')->default('pending'); // e.g., pending, processing, shipped, completed, cancelled
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
        $table->decimal('total_amount', 8, 2)->change();
    }
};
