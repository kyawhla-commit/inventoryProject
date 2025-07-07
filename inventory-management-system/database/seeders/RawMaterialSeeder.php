<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\RawMaterial;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

class RawMaterialSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $suppliers = Supplier::all();

        if ($suppliers->isEmpty()) {
            $this->command->info('No suppliers found, skipping raw material seeding.');
            return;
        }

        $materials = [
            ['name' => 'Flour', 'unit' => 'kg', 'cost' => 1.5, 'qty' => 100, 'min_stock' => 20],
            ['name' => 'Sugar', 'unit' => 'kg', 'cost' => 2, 'qty' => 50, 'min_stock' => 15],
            ['name' => 'Butter', 'unit' => 'kg', 'cost' => 5, 'qty' => 10, 'min_stock' => 12], // Low stock
            ['name' => 'Eggs', 'unit' => 'dozen', 'cost' => 3, 'qty' => 20, 'min_stock' => 5],
            ['name' => 'Milk', 'unit' => 'liter', 'cost' => 1.2, 'qty' => 30, 'min_stock' => 10],
            ['name' => 'Chocolate Chips', 'unit' => 'kg', 'cost' => 8, 'qty' => 5, 'min_stock' => 8], // Low stock
            ['name' => 'Yeast', 'unit' => 'g', 'cost' => 0.1, 'qty' => 500, 'min_stock' => 100],
        ];

        foreach ($materials as $material) {
            RawMaterial::create([
                'name' => $material['name'],
                'description' => 'High-quality ' . strtolower($material['name']) . ' for production.',
                'quantity' => $material['qty'],
                'unit' => $material['unit'],
                'cost_per_unit' => $material['cost'],
                'minimum_stock_level' => $material['min_stock'],
                'supplier_id' => $suppliers->random()->id,
            ]);
        }
    }
}
