<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

class PurchaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $suppliers = Supplier::all();
        $products = Product::all();

        if ($suppliers->isEmpty() || $products->isEmpty()) {
            $this->command->info('No suppliers or products found, skipping purchase seeding.');
            return;
        }

        for ($i = 0; $i < 20; $i++) {
            DB::transaction(function () use ($suppliers, $products) {
                $purchase = Purchase::create([
                    'supplier_id' => $suppliers->random()->id,
                    'purchase_date' => now()->subDays(rand(1, 365)),
                    'total_amount' => 0, // Will be updated later
                ]);

                $totalAmount = 0;
                $productCount = rand(1, 5);
                $selectedProducts = $products->random($productCount);

                foreach ($selectedProducts as $product) {
                    $quantity = rand(5, 20);
                    $cost = $product->cost;
                    $totalAmount += $quantity * $cost;

                    PurchaseItem::create([
                        'purchase_id' => $purchase->id,
                        'product_id' => $product->id,
                        'quantity' => $quantity,
                        'unit_cost' => $cost,
                    ]);

                    // Update product stock
                    $product->increment('quantity', $quantity);
                }

                $purchase->update(['total_amount' => $totalAmount]);
            });
        }
    }
}
