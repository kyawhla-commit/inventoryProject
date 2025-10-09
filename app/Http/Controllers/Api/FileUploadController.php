<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\RawMaterial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileUploadController extends Controller
{
    public function uploadProductImage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120', // 5MB max
            'is_primary' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $product = Product::find($request->product_id);
        $file = $request->file('image');
        
        // Generate unique filename
        $filename = 'product_' . $product->id . '_' . time() . '_' . Str::random(8) . '.' . $file->getClientOriginalExtension();
        
        // Store file
        $path = $file->storeAs('products/images', $filename, 'public');
        
        // Get file info
        $fileInfo = [
            'original_name' => $file->getClientOriginalName(),
            'filename' => $filename,
            'path' => $path,
            'url' => Storage::url($path),
            'size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'uploaded_at' => now()->toISOString(),
        ];

        // Update product with image info (simplified - you might want a separate images table)
        $images = $product->images ?? [];
        $images[] = $fileInfo;
        
        $updateData = ['images' => $images];
        
        // Set as primary image if requested or if it's the first image
        if ($request->boolean('is_primary') || empty($product->image)) {
            $updateData['image'] = Storage::url($path);
        }
        
        $product->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Product image uploaded successfully',
            'data' => [
                'product_id' => $product->id,
                'file_info' => $fileInfo,
                'is_primary' => $request->boolean('is_primary') || empty($product->image),
            ]
        ], 201);
    }

    public function uploadDocument(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:product,raw_material,general',
            'item_id' => 'required_unless:type,general|integer',
            'document' => 'required|file|mimes:pdf,doc,docx,xls,xlsx,txt,csv|max:10240', // 10MB max
            'document_type' => 'required|in:specification,manual,certificate,invoice,other',
            'description' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $file = $request->file('document');
        $type = $request->type;
        $itemId = $request->item_id;
        
        // Validate item exists
        if ($type !== 'general') {
            if ($type === 'product' && !Product::find($itemId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found'
                ], 404);
            } elseif ($type === 'raw_material' && !RawMaterial::find($itemId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Raw material not found'
                ], 404);
            }
        }
        
        // Generate unique filename
        $filename = $type . '_' . ($itemId ?? 'general') . '_' . time() . '_' . Str::random(8) . '.' . $file->getClientOriginalExtension();
        
        // Store file
        $path = $file->storeAs('documents/' . $type, $filename, 'public');
        
        $documentInfo = [
            'type' => $type,
            'item_id' => $itemId,
            'document_type' => $request->document_type,
            'description' => $request->description,
            'original_name' => $file->getClientOriginalName(),
            'filename' => $filename,
            'path' => $path,
            'url' => Storage::url($path),
            'size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'uploaded_at' => now()->toISOString(),
        ];

        return response()->json([
            'success' => true,
            'message' => 'Document uploaded successfully',
            'data' => $documentInfo
        ], 201);
    }

    public function uploadBulkImages(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'images' => 'required|array|min:1|max:10',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:5120',
            'product_ids' => 'required|array',
            'product_ids.*' => 'exists:products,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $images = $request->file('images');
        $productIds = $request->product_ids;
        $uploadedFiles = [];

        foreach ($images as $index => $file) {
            if (isset($productIds[$index])) {
                $productId = $productIds[$index];
                $product = Product::find($productId);
                
                if ($product) {
                    // Generate unique filename
                    $filename = 'product_' . $productId . '_' . time() . '_' . $index . '.' . $file->getClientOriginalExtension();
                    
                    // Store file
                    $path = $file->storeAs('products/images', $filename, 'public');
                    
                    $fileInfo = [
                        'product_id' => $productId,
                        'original_name' => $file->getClientOriginalName(),
                        'filename' => $filename,
                        'path' => $path,
                        'url' => Storage::url($path),
                        'size' => $file->getSize(),
                    ];
                    
                    // Update product
                    $images = $product->images ?? [];
                    $images[] = $fileInfo;
                    $product->update(['images' => $images]);
                    
                    $uploadedFiles[] = $fileInfo;
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Bulk images uploaded successfully',
            'data' => [
                'uploaded_count' => count($uploadedFiles),
                'files' => $uploadedFiles,
            ]
        ], 201);
    }

    public function deleteFile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'path' => 'required|string',
            'type' => 'required|in:product_image,document',
            'item_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $path = $request->path;
        $type = $request->type;
        $itemId = $request->item_id;

        // Check if file exists
        if (!Storage::disk('public')->exists($path)) {
            return response()->json([
                'success' => false,
                'message' => 'File not found'
            ], 404);
        }

        // Delete file
        Storage::disk('public')->delete($path);

        // Update database if it's a product image
        if ($type === 'product_image') {
            $product = Product::find($itemId);
            if ($product) {
                $images = $product->images ?? [];
                $images = array_filter($images, function($img) use ($path) {
                    return $img['path'] !== $path;
                });
                $product->update(['images' => array_values($images)]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'File deleted successfully'
        ]);
    }

    public function getFiles(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:product,raw_material,general',
            'item_id' => 'required_unless:type,general|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $type = $request->type;
        $itemId = $request->item_id;

        if ($type === 'product') {
            $product = Product::find($itemId);
            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'type' => 'product',
                    'item_id' => $itemId,
                    'item_name' => $product->name,
                    'primary_image' => $product->image,
                    'images' => $product->images ?? [],
                ]
            ]);
        }

        // For general files or other types, you'd implement similar logic
        return response()->json([
            'success' => true,
            'data' => [
                'type' => $type,
                'item_id' => $itemId,
                'files' => [], // Implement file listing logic
            ]
        ]);
    }

    public function getStorageInfo()
    {
        $disk = Storage::disk('public');
        
        // Calculate storage usage (simplified)
        $totalSize = 0;
        $fileCount = 0;
        
        $directories = ['products/images', 'documents/product', 'documents/raw_material', 'documents/general'];
        
        foreach ($directories as $dir) {
            if ($disk->exists($dir)) {
                $files = $disk->allFiles($dir);
                $fileCount += count($files);
                
                foreach ($files as $file) {
                    $totalSize += $disk->size($file);
                }
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'total_files' => $fileCount,
                'total_size_bytes' => $totalSize,
                'total_size_mb' => round($totalSize / 1024 / 1024, 2),
                'directories' => $directories,
                'storage_path' => storage_path('app/public'),
            ]
        ]);
    }
}