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
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('voucher_id')->nullable()->constrained()->onDelete('set null')->after('total_amount');
            $table->string('voucher_code')->nullable()->after('voucher_id');
            $table->decimal('subtotal', 10, 2)->default(0)->after('voucher_code');
            $table->decimal('discount_amount', 10, 2)->default(0)->after('subtotal');
            // total_amount will now be subtotal - discount_amount
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['voucher_id']);
            $table->dropColumn(['voucher_id', 'voucher_code', 'subtotal', 'discount_amount']);
        });
    }
};