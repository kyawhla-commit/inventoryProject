<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            'Electronics',
            'Books',
            'Clothing',
            'Home & Kitchen',
            'Toys & Games',
        ];

        foreach ($categories as $category) {
            Category::create(['name' => $category]);
        }
    }
}
