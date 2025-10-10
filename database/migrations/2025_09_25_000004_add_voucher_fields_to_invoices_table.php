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
        Schema::table('invoices', function (Blueprint $table) {
            // Add voucher_id if it doesn't exist
            if (!Schema::hasColumn('invoices', 'voucher_id')) {
                $table->foreignId('voucher_id')->nullable()->constrained()->onDelete('set null')->after('total_amount');
            }
            
            // Add voucher_code if it doesn't exist
            if (!Schema::hasColumn('invoices', 'voucher_code')) {
                $table->string('voucher_code')->nullable()->after('voucher_id');
            }
            
            // Only add discount_amount if it doesn't exist
            if (!Schema::hasColumn('invoices', 'discount_amount')) {
                $table->decimal('discount_amount', 10, 2)->default(0)->after('voucher_code');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // Only drop foreign key if it exists
            if (Schema::hasColumn('invoices', 'voucher_id')) {
                $table->dropForeign(['voucher_id']);
            }
            
            // Drop columns only if they exist
            $columnsToDrop = [];
            if (Schema::hasColumn('invoices', 'voucher_id')) {
                $columnsToDrop[] = 'voucher_id';
            }
            if (Schema::hasColumn('invoices', 'voucher_code')) {
                $columnsToDrop[] = 'voucher_code';
            }
            if (Schema::hasColumn('invoices', 'discount_amount')) {
                $columnsToDrop[] = 'discount_amount';
            }
            
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};