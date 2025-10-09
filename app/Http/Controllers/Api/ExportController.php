<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\RawMaterial;
use App\Models\Sale;
use App\Models\Purchase;
use App\Models\InventoryTransaction;
use App\Models\ProductionPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ExportController extends Controller
{
    public function exportProducts(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'format' => 'required|in:csv,json,excel',
            'category_id' => 'nullable|exists:categories,id',
            'include_images' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $format = $request->format;
        $query = Product::with('category');

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $products = $query->get();

        // Transform data for export
        $exportData = $products->map(function ($product) use ($request) {
            $data = [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'description' => $product->description,
                'category' => $product->category->name ?? '',
                'price' => $product->price,
                'cost' => $product->cost,
                'quantity' => $product->quantity,
                'min_quantity' => $product->min_quantity,
                'unit' => $product->unit,
                'status' => $product->status,
                'created_at' => $product->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $product->updated_at->format('Y-m-d H:i:s'),
            ];

            if ($request->boolean('include_images')) {
                $data['primary_image'] = $product->image;
                $data['images_count'] = count($product->images ?? []);
            }

            return $data;
        });

        $filename = 'products_export_' . now()->format('Y-m-d_H-i-s');
        $filePath = $this->generateExportFile($exportData, $format, $filename);

        return response()->json([
            'success' => true,
            'data' => [
                'format' => $format,
                'filename' => $filename . '.' . $format,
                'file_path' => $filePath,
                'download_url' => Storage::url($filePath),
                'record_count' => $exportData->count(),
                'generated_at' => now()->toISOString(),
            ]
        ]);
    }

    public function exportInventoryTransactions(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'format' => 'required|in:csv,json,excel',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'type' => 'nullable|in:in,out,adjustment',
            'source' => 'nullable|in:sale,purchase,production,adjustment,return,transfer,waste',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $format = $request->format;
        $query = InventoryTransaction::with(['product', 'rawMaterial', 'createdBy']);

        // Apply filters
        if ($request->has('date_from')) {
            $query->whereDate('transaction_date', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->whereDate('transaction_date', '<=', $request->date_to);
        }
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }
        if ($request->has('source')) {
            $query->where('source', $request->source);
        }

        $transactions = $query->orderBy('transaction_date', 'desc')->get();

        // Transform data for export
        $exportData = $transactions->map(function ($transaction) {
            return [
                'transaction_number' => $transaction->transaction_number,
                'date' => $transaction->transaction_date->format('Y-m-d H:i:s'),
                'type' => $transaction->type,
                'source' => $transaction->source,
                'item_type' => $transaction->product_id ? 'product' : 'raw_material',
                'item_name' => $transaction->product ? $transaction->product->name : ($transaction->rawMaterial ? $transaction->rawMaterial->name : ''),
                'quantity' => $transaction->quantity,
                'unit' => $transaction->unit,
                'unit_cost' => $transaction->unit_cost,
                'total_cost' => $transaction->total_cost,
                'stock_before' => $transaction->stock_before,
                'stock_after' => $transaction->stock_after,
                'reason' => $transaction->reason,
                'notes' => $transaction->notes,
                'created_by' => $transaction->createdBy->name ?? '',
            ];
        });

        $filename = 'inventory_transactions_export_' . now()->format('Y-m-d_H-i-s');
        $filePath = $this->generateExportFile($exportData, $format, $filename);

        return response()->json([
            'success' => true,
            'data' => [
                'format' => $format,
                'filename' => $filename . '.' . $format,
                'file_path' => $filePath,
                'download_url' => Storage::url($filePath),
                'record_count' => $exportData->count(),
                'filters_applied' => $request->only(['date_from', 'date_to', 'type', 'source']),
                'generated_at' => now()->toISOString(),
            ]
        ]);
    }

    public function exportSalesReport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'format' => 'required|in:csv,json,excel',
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
            'include_items' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $format = $request->format;
        $dateFrom = $request->date_from;
        $dateTo = $request->date_to;
        $includeItems = $request->boolean('include_items');

        $query = Sale::with(['customer', 'items.product'])
            ->whereBetween('sale_date', [$dateFrom, $dateTo]);

        $sales = $query->get();

        $exportData = collect();

        foreach ($sales as $sale) {
            $saleData = [
                'sale_id' => $sale->id,
                'sale_date' => $sale->sale_date,
                'customer_name' => $sale->customer->name ?? 'Walk-in Customer',
                'customer_email' => $sale->customer->email ?? '',
                'total_amount' => $sale->total_amount,
                'items_count' => $sale->items->count(),
            ];

            if ($includeItems) {
                foreach ($sale->items as $item) {
                    $itemData = array_merge($saleData, [
                        'product_name' => $item->product->name ?? '',
                        'product_sku' => $item->product->sku ?? '',
                        'quantity' => $item->quantity,
                        'unit_price' => $item->unit_price,
                        'line_total' => $item->quantity * $item->unit_price,
                    ]);
                    $exportData->push($itemData);
                }
            } else {
                $exportData->push($saleData);
            }
        }

        $filename = 'sales_report_' . $dateFrom . '_to_' . $dateTo . '_' . now()->format('H-i-s');
        $filePath = $this->generateExportFile($exportData, $format, $filename);

        return response()->json([
            'success' => true,
            'data' => [
                'format' => $format,
                'filename' => $filename . '.' . $format,
                'file_path' => $filePath,
                'download_url' => Storage::url($filePath),
                'record_count' => $exportData->count(),
                'date_range' => ['from' => $dateFrom, 'to' => $dateTo],
                'include_items' => $includeItems,
                'generated_at' => now()->toISOString(),
            ]
        ]);
    }

    public function createBackup(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tables' => 'nullable|array',
            'tables.*' => 'string',
            'include_files' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $tables = $request->get('tables', [
            'products',
            'raw_materials',
            'categories',
            'suppliers',
            'customers',
            'sales',
            'sale_items',
            'purchases',
            'purchase_items',
            'inventory_transactions',
            'production_plans',
            'production_plan_items',
            'recipes',
            'recipe_items'
        ]);

        $includeFiles = $request->boolean('include_files');
        $backupData = [];

        // Export data from each table
        foreach ($tables as $table) {
            try {
                $data = $this->getTableData($table);
                $backupData[$table] = $data;
            } catch (\Exception $e) {
                // Skip tables that don't exist or have errors
                continue;
            }
        }

        // Create backup metadata
        $metadata = [
            'backup_created_at' => now()->toISOString(),
            'tables_included' => array_keys($backupData),
            'record_counts' => array_map('count', $backupData),
            'total_records' => array_sum(array_map('count', $backupData)),
            'include_files' => $includeFiles,
            'version' => '1.0',
        ];

        $backupData['_metadata'] = $metadata;

        // Generate backup file
        $filename = 'inventory_backup_' . now()->format('Y-m-d_H-i-s');
        $filePath = $this->generateExportFile(collect($backupData), 'json', $filename);

        // TODO: If include_files is true, create a zip with files
        $fileBackupInfo = null;
        if ($includeFiles) {
            $fileBackupInfo = [
                'message' => 'File backup not implemented yet',
                'files_location' => storage_path('app/public'),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'backup_filename' => $filename . '.json',
                'backup_path' => $filePath,
                'download_url' => Storage::url($filePath),
                'metadata' => $metadata,
                'file_backup' => $fileBackupInfo,
            ]
        ]);
    }

    public function getExportHistory()
    {
        // Get list of export files
        $exportFiles = Storage::disk('public')->files('exports');

        $history = collect($exportFiles)->map(function ($file) {
            try {
                $size = Storage::disk('public')->size($file);
                $lastModified = Storage::disk('public')->lastModified($file);
            } catch (\Exception $e) {
                $size = 0;
                $lastModified = time();
            }

            return [
                'filename' => basename($file),
                'path' => $file,
                'size' => $size,
                'size_mb' => round($size / 1024 / 1024, 2),
                'created_at' => date('Y-m-d H:i:s', $lastModified),
                'download_url' => Storage::url($file),
            ];
        })->sortByDesc('created_at')->values();

        return response()->json([
            'success' => true,
            'data' => [
                'export_files' => $history,
                'total_files' => $history->count(),
                'total_size_mb' => $history->sum('size_mb'),
            ]
        ]);
    }

    public function deleteExport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'filename' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $filename = $request->filename;
        $filePath = 'exports/' . $filename;

        if (!Storage::disk('public')->exists($filePath)) {
            return response()->json([
                'success' => false,
                'message' => 'Export file not found'
            ], 404);
        }

        Storage::disk('public')->delete($filePath);

        return response()->json([
            'success' => true,
            'message' => 'Export file deleted successfully'
        ]);
    }

    private function generateExportFile($data, $format, $filename)
    {
        $directory = 'exports';
        Storage::disk('public')->makeDirectory($directory);

        switch ($format) {
            case 'csv':
                return $this->generateCSV($data, $directory, $filename);
            case 'json':
                return $this->generateJSON($data, $directory, $filename);
            case 'excel':
                // For now, generate CSV (you could implement Excel using PhpSpreadsheet)
                return $this->generateCSV($data, $directory, $filename);
            default:
                return $this->generateJSON($data, $directory, $filename);
        }
    }

    private function generateCSV($data, $directory, $filename)
    {
        $filePath = $directory . '/' . $filename . '.csv';

        if ($data->isEmpty()) {
            Storage::disk('public')->put($filePath, 'No data to export');
            return $filePath;
        }

        $csv = '';
        $headers = array_keys($data->first());
        $csv .= implode(',', $headers) . "\n";

        foreach ($data as $row) {
            $csv .= implode(',', array_map(function ($value) {
                return '"' . str_replace('"', '""', $value) . '"';
            }, array_values($row))) . "\n";
        }

        Storage::disk('public')->put($filePath, $csv);
        return $filePath;
    }

    private function generateJSON($data, $directory, $filename)
    {
        $filePath = $directory . '/' . $filename . '.json';
        $json = json_encode($data, JSON_PRETTY_PRINT);
        Storage::disk('public')->put($filePath, $json);
        return $filePath;
    }

    private function getTableData($table)
    {
        // Simple table data extraction (you might want to use proper models)
        return DB::table($table)->get()->toArray();
    }
}
