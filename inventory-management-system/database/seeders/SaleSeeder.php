<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Product;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

class SaleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $customers = Customer::all();
        $products = Product::where('quantity', '>', 0)->get();

        if ($customers->isEmpty() || $products->isEmpty()) {
            $this->command->info('No customers or available products found, skipping sale seeding.');
            return;
        }

        for ($i = 0; $i < 50; $i++) {
            DB::transaction(function () use ($customers, &$products) {
                $sale = Sale::create([
                    'customer_id' => $customers->random()->id,
                    'sale_date' => now()->subDays(rand(1, 30)),
                    'total_amount' => 0, // Will be updated later
                ]);

                $totalAmount = 0;
                $productCount = rand(1, 3);
                $selectedProducts = $products->random($productCount);

                foreach ($selectedProducts as $product) {
                    $quantityToSell = rand(1, min(5, $product->quantity));

                    if ($quantityToSell > 0) {
                        $price = $product->price;
                        $totalAmount += $quantityToSell * $price;

                        SaleItem::create([
                            'sale_id' => $sale->id,
                            'product_id' => $product->id,
                            'quantity' => $quantityToSell,
                            'unit_price' => $price,
                        ]);

                        // Update product stock
                        $product->decrement('quantity', $quantityToSell);
                    }
                }

                $sale->update(['total_amount' => $totalAmount]);

                // Refresh the products collection to get updated quantities
                $products = Product::where('quantity', '>', 0)->get();
            });
        }
    }
}
