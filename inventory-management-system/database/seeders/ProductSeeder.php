<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = Category::pluck('id');

        if ($categories->isEmpty()) {
            $this->command->info('No categories found, skipping product seeding.');
            return;
        }

        // Ensure the images directory exists
        if (!Storage::disk('public')->exists('images')) {
            Storage::disk('public')->makeDirectory('images');
        }

        for ($i = 0; $i < 50; $i++) {
            $imageName = null;
            try {
                // Get a random image from picsum.photos with a longer timeout
                $imageUrl = 'https://placehold.co/400x300';
                $response = Http::timeout(30)->get($imageUrl);

                if ($response->successful()) {
                    $imageContents = $response->body();
                    $imageName = 'product_' . uniqid() . '.jpg';
                    Storage::disk('public')->put('images/' . $imageName, $imageContents);
                } else {
                    $this->command->warn("Failed to download image for product " . ($i + 1) . ". Status: " . $response->status());
                }
            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                $this->command->warn("Could not connect to image service for product " . ($i + 1) . ". Error: " . $e->getMessage());
            }

            Product::create([
                'name' => 'Product ' . ($i + 1),
                'description' => 'This is a sample description for product ' . ($i + 1) . '.',
                'barcode' => 'SKU' . str_pad($i + 1, 5, '0', STR_PAD_LEFT),
                'image' => $imageName, // This will be null if image download failed
                'quantity' => rand(10, 100),
                'price' => rand(100, 1000) / 10,
                'cost' => rand(50, 800) / 10,
                'minimum_stock_level' => 10,
                'category_id' => $categories->random(),
            ]);
        }
    }
}
