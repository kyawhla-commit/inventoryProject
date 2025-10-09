<?php

namespace App\Services;

use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\RawMaterial;
use Illuminate\Support\Facades\Auth;

class InventoryTransactionService
{
    /**
     * Record a sale transaction
     */
    public static function recordSale($saleItem)
    {
        if (!$saleItem->product) return;

        $product = $saleItem->product;
        $stockBefore = $product->quantity + $saleItem->quantity; // Stock before the sale

        self::createTransaction([
            'type' => 'out',
            'source' => 'sale',
            'product_id' => $product->id,
            'quantity' => -$saleItem->quantity, // Negative for outbound
            'unit_cost' => $saleItem->unit_price,
            'total_cost' => $saleItem->quantity * $saleItem->unit_price,
            'unit' => $product->unit ?? 'pcs',
            'stock_before' => $stockBefore,
            'stock_after' => $product->quantity,
            'sale_id' => $saleItem->sale_id,
            'notes' => "Sale transaction for order #{$saleItem->sale_id}",
        ]);
    }

    /**
     * Record a purchase transaction
     */
    public static function recordPurchase($purchaseItem)
    {
        if ($purchaseItem->product_id) {
            $product = $purchaseItem->product;
            $stockBefore = $product->quantity - $purchaseItem->quantity; // Stock before the purchase

            self::createTransaction([
                'type' => 'in',
                'source' => 'purchase',
                'product_id' => $product->id,
                'quantity' => $purchaseItem->quantity,
                'unit_cost' => $purchaseItem->unit_cost,
                'total_cost' => $purchaseItem->quantity * $purchaseItem->unit_cost,
                'unit' => $product->unit ?? 'pcs',
                'stock_before' => $stockBefore,
                'stock_after' => $product->quantity,
                'purchase_id' => $purchaseItem->purchase_id,
                'notes' => "Purchase transaction for PO #{$purchaseItem->purchase_id}",
            ]);
        } elseif ($purchaseItem->raw_material_id) {
            $rawMaterial = $purchaseItem->rawMaterial;
            $stockBefore = $rawMaterial->current_stock - $purchaseItem->quantity; // Stock before the purchase

            self::createTransaction([
                'type' => 'in',
                'source' => 'purchase',
                'raw_material_id' => $rawMaterial->id,
                'quantity' => $purchaseItem->quantity,
                'unit_cost' => $purchaseItem->unit_cost,
                'total_cost' => $purchaseItem->quantity * $purchaseItem->unit_cost,
                'unit' => $rawMaterial->unit,
                'stock_before' => $stockBefore,
                'stock_after' => $rawMaterial->current_stock,
                'purchase_id' => $purchaseItem->purchase_id,
                'notes' => "Purchase transaction for PO #{$purchaseItem->purchase_id}",
            ]);
        }
    }

    /**
     * Record production consumption (raw materials out)
     */
    public static function recordProductionConsumption($productionPlanItem, $rawMaterialId, $quantity, $cost)
    {
        $rawMaterial = RawMaterial::find($rawMaterialId);
        if (!$rawMaterial) return;

        $stockBefore = $rawMaterial->current_stock + $quantity; // Stock before consumption

        self::createTransaction([
            'type' => 'out',
            'source' => 'production',
            'raw_material_id' => $rawMaterial->id,
            'quantity' => -$quantity, // Negative for outbound
            'unit_cost' => $cost,
            'total_cost' => $quantity * $cost,
            'unit' => $rawMaterial->unit,
            'stock_before' => $stockBefore,
            'stock_after' => $rawMaterial->current_stock,
            'production_plan_item_id' => $productionPlanItem->id,
            'notes' => "Raw material consumption for production #{$productionPlanItem->id}",
        ]);
    }

    /**
     * Record production output (finished products in)
     */
    public static function recordProductionOutput($productionPlanItem)
    {
        if (!$productionPlanItem->product) return;

        $product = $productionPlanItem->product;
        $quantity = $productionPlanItem->actual_quantity;
        $stockBefore = $product->quantity - $quantity; // Stock before production

        self::createTransaction([
            'type' => 'in',
            'source' => 'production',
            'product_id' => $product->id,
            'quantity' => $quantity,
            'unit_cost' => $productionPlanItem->actual_material_cost / $quantity,
            'total_cost' => $productionPlanItem->actual_material_cost,
            'unit' => $product->unit ?? 'pcs',
            'stock_before' => $stockBefore,
            'stock_after' => $product->quantity,
            'production_plan_item_id' => $productionPlanItem->id,
            'notes' => "Production output for plan #{$productionPlanItem->production_plan_id}",
        ]);
    }

    /**
     * Record stock adjustment
     */
    public static function recordAdjustment($itemType, $itemId, $quantityChange, $reason, $notes = null)
    {
        if ($itemType === 'product') {
            $product = Product::find($itemId);
            if (!$product) return;

            $stockBefore = $product->quantity - $quantityChange;

            self::createTransaction([
                'type' => 'adjustment',
                'source' => 'adjustment',
                'product_id' => $product->id,
                'quantity' => $quantityChange,
                'unit' => $product->unit ?? 'pcs',
                'stock_before' => $stockBefore,
                'stock_after' => $product->quantity,
                'reason' => $reason,
                'notes' => $notes,
            ]);
        } elseif ($itemType === 'raw_material') {
            $rawMaterial = RawMaterial::find($itemId);
            if (!$rawMaterial) return;

            $stockBefore = $rawMaterial->current_stock - $quantityChange;

            self::createTransaction([
                'type' => 'adjustment',
                'source' => 'adjustment',
                'raw_material_id' => $rawMaterial->id,
                'quantity' => $quantityChange,
                'unit' => $rawMaterial->unit,
                'stock_before' => $stockBefore,
                'stock_after' => $rawMaterial->current_stock,
                'reason' => $reason,
                'notes' => $notes,
            ]);
        }
    }

    /**
     * Create an inventory transaction record
     */
    private static function createTransaction($data)
    {
        $data['transaction_date'] = now();
        
        // Add created_by if user is authenticated
        if (Auth::check()) {
            $data['created_by'] = Auth::id();
        }

        return InventoryTransaction::create($data);
    }

    /**
     * Get stock level at a specific date
     */
    public static function getStockAtDate($itemType, $itemId, $date)
    {
        $field = $itemType === 'product' ? 'product_id' : 'raw_material_id';
        
        // Get current stock
        if ($itemType === 'product') {
            $currentStock = Product::find($itemId)->quantity ?? 0;
        } else {
            $currentStock = RawMaterial::find($itemId)->current_stock ?? 0;
        }

        // Get all transactions after the specified date
        $transactionsAfterDate = InventoryTransaction::where($field, $itemId)
            ->where('transaction_date', '>', $date)
            ->sum('quantity');

        // Calculate stock at the specified date
        return $currentStock - $transactionsAfterDate;
    }

    /**
     * Get stock movements summary for a date range
     */
    public static function getMovementsSummary($startDate, $endDate, $itemType = null, $itemId = null)
    {
        $query = InventoryTransaction::whereBetween('transaction_date', [$startDate, $endDate]);

        if ($itemType && $itemId) {
            $field = $itemType === 'product' ? 'product_id' : 'raw_material_id';
            $query->where($field, $itemId);
        }

        $transactions = $query->get();

        return [
            'total_transactions' => $transactions->count(),
            'total_in' => $transactions->where('type', 'in')->sum('quantity'),
            'total_out' => abs($transactions->where('type', 'out')->sum('quantity')),
            'total_adjustments' => $transactions->where('type', 'adjustment')->sum('quantity'),
            'net_change' => $transactions->sum('quantity'),
            'total_value' => $transactions->sum('total_cost'),
        ];
    }
}