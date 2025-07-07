<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Customer;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $customers = Customer::all();
        $products = Product::all();

        if ($customers->isEmpty() || $products->isEmpty()) {
            $this->command->info('Cannot seed orders without customers and products.');
            return;
        }

        for ($i = 0; $i < 20; $i++) {
            $customer = $customers->random();
            $orderDate = Carbon::now()->subDays(rand(1, 365));
            $status = ['pending', 'processing', 'shipped', 'completed', 'cancelled'][rand(0, 4)];

            $order = Order::create([
                'customer_id' => $customer->id,
                'order_date' => $orderDate,
                'total_amount' => 0, // Will be updated after adding items
                'status' => $status,
            ]);

            $totalAmount = 0;
            $numberOfItems = rand(1, 5);

            for ($j = 0; $j < $numberOfItems; $j++) {
                $product = $products->random();
                $quantity = rand(1, 3);
                $price = $product->price;
                $itemTotal = $quantity * $price;

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'price' => $price,
                ]);

                $totalAmount += $itemTotal;
            }

            $order->update(['total_amount' => $totalAmount]);
        }
    }
}
