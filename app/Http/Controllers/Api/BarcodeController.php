<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\RawMaterial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class BarcodeController extends Controller
{
    public function generateBarcode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:product,raw_material',
            'item_id' => 'required|integer',
            'format' => 'nullable|in:code128,ean13,qr',
            'size' => 'nullable|in:small,medium,large',
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
        $format = $request->get('format', 'code128');
        $size = $request->get('size', 'medium');

        // Get item details
        if ($type === 'product') {
            $item = Product::find($itemId);
            if (!$item) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found'
                ], 404);
            }
            $code = $item->sku ?: $this->generateSKU('PRD', $itemId);
        } else {
            $item = RawMaterial::find($itemId);
            if (!$item) {
                return response()->json([
                    'success' => false,
                    'message' => 'Raw material not found'
                ], 404);
            }
            $code = $this->generateSKU('RM', $itemId);
        }

        // Generate barcode data
        $barcodeData = $this->createBarcodeData($code, $format, $size);
        
        // Create QR code data if requested
        $qrData = null;
        if ($format === 'qr') {
            $qrData = $this->createQRData($item, $type, $code);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'item_type' => $type,
                'item_id' => $itemId,
                'item_name' => $item->name,
                'code' => $code,
                'format' => $format,
                'size' => $size,
                'barcode_data' => $barcodeData,
                'qr_data' => $qrData,
                'svg_url' => route('api.barcode.svg', [
                    'code' => $code,
                    'format' => $format,
                    'size' => $size
                ]),
                'png_url' => route('api.barcode.png', [
                    'code' => $code,
                    'format' => $format,
                    'size' => $size
                ]),
            ]
        ]);
    }

    public function generateBulkBarcodes(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'items' => 'required|array|min:1|max:100',
            'items.*.type' => 'required|in:product,raw_material',
            'items.*.item_id' => 'required|integer',
            'format' => 'nullable|in:code128,ean13,qr',
            'size' => 'nullable|in:small,medium,large',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $format = $request->get('format', 'code128');
        $size = $request->get('size', 'medium');
        $barcodes = [];

        foreach ($request->items as $itemData) {
            $type = $itemData['type'];
            $itemId = $itemData['item_id'];

            // Get item details
            if ($type === 'product') {
                $item = Product::find($itemId);
                $code = $item ? ($item->sku ?: $this->generateSKU('PRD', $itemId)) : null;
            } else {
                $item = RawMaterial::find($itemId);
                $code = $item ? $this->generateSKU('RM', $itemId) : null;
            }

            if ($item && $code) {
                $barcodes[] = [
                    'item_type' => $type,
                    'item_id' => $itemId,
                    'item_name' => $item->name,
                    'code' => $code,
                    'barcode_data' => $this->createBarcodeData($code, $format, $size),
                    'svg_url' => route('api.barcode.svg', [
                        'code' => $code,
                        'format' => $format,
                        'size' => $size
                    ]),
                ];
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'format' => $format,
                'size' => $size,
                'total_items' => count($barcodes),
                'barcodes' => $barcodes,
            ]
        ]);
    }

    public function scanBarcode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $code = $request->code;
        
        // Try to find product by SKU
        $product = Product::where('sku', $code)->first();
        if ($product) {
            return response()->json([
                'success' => true,
                'data' => [
                    'found' => true,
                    'type' => 'product',
                    'item' => $product->load('category'),
                    'stock_info' => [
                        'current_stock' => $product->quantity,
                        'min_stock' => $product->min_quantity,
                        'is_low_stock' => $product->quantity <= $product->min_quantity,
                    ],
                ]
            ]);
        }

        // Try to find raw material by generated code pattern
        if (Str::startsWith($code, 'RM-')) {
            $itemId = (int) Str::after($code, 'RM-');
            $rawMaterial = RawMaterial::find($itemId);
            if ($rawMaterial) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'found' => true,
                        'type' => 'raw_material',
                        'item' => $rawMaterial,
                        'stock_info' => [
                            'current_stock' => $rawMaterial->current_stock,
                            'min_stock' => $rawMaterial->min_stock_level,
                            'is_low_stock' => $rawMaterial->current_stock <= $rawMaterial->min_stock_level,
                        ],
                    ]
                ]);
            }
        }

        // Try to find product by generated code pattern
        if (Str::startsWith($code, 'PRD-')) {
            $itemId = (int) Str::after($code, 'PRD-');
            $product = Product::find($itemId);
            if ($product) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'found' => true,
                        'type' => 'product',
                        'item' => $product->load('category'),
                        'stock_info' => [
                            'current_stock' => $product->quantity,
                            'min_stock' => $product->min_quantity,
                            'is_low_stock' => $product->quantity <= $product->min_quantity,
                        ],
                    ]
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'found' => false,
                'code' => $code,
                'message' => 'No item found with this barcode'
            ]
        ]);
    }

    public function generateSVG(Request $request)
    {
        $code = $request->get('code');
        $format = $request->get('format', 'code128');
        $size = $request->get('size', 'medium');

        if (!$code) {
            return response()->json(['error' => 'Code parameter required'], 400);
        }

        $svg = $this->createBarcodeSVG($code, $format, $size);
        
        return response($svg)
            ->header('Content-Type', 'image/svg+xml')
            ->header('Cache-Control', 'public, max-age=3600');
    }

    public function generatePNG(Request $request)
    {
        $code = $request->get('code');
        $format = $request->get('format', 'code128');
        $size = $request->get('size', 'medium');

        if (!$code) {
            return response()->json(['error' => 'Code parameter required'], 400);
        }

        // For now, return a placeholder response
        // In a real implementation, you'd convert SVG to PNG
        return response()->json([
            'message' => 'PNG generation not implemented yet. Use SVG endpoint.',
            'svg_url' => route('api.barcode.svg', compact('code', 'format', 'size'))
        ]);
    }

    private function generateSKU($prefix, $id)
    {
        return $prefix . '-' . str_pad($id, 6, '0', STR_PAD_LEFT);
    }

    private function createBarcodeData($code, $format, $size)
    {
        $dimensions = $this->getBarcodeSize($size);
        
        return [
            'code' => $code,
            'format' => $format,
            'width' => $dimensions['width'],
            'height' => $dimensions['height'],
            'text' => $code,
        ];
    }

    private function createQRData($item, $type, $code)
    {
        return [
            'code' => $code,
            'item_type' => $type,
            'item_id' => $item->id,
            'item_name' => $item->name,
            'current_stock' => $type === 'product' ? $item->quantity : $item->current_stock,
            'unit' => $type === 'product' ? ($item->unit ?? 'pcs') : $item->unit,
            'generated_at' => now()->toISOString(),
        ];
    }

    private function getBarcodeSize($size)
    {
        return match($size) {
            'small' => ['width' => 200, 'height' => 50],
            'large' => ['width' => 400, 'height' => 100],
            default => ['width' => 300, 'height' => 75], // medium
        };
    }

    private function createBarcodeSVG($code, $format, $size)
    {
        $dimensions = $this->getBarcodeSize($size);
        $width = $dimensions['width'];
        $height = $dimensions['height'];
        
        // Simple SVG barcode representation (Code 128 style)
        $svg = '<?xml version="1.0" encoding="UTF-8"?>';
        $svg .= '<svg width="' . $width . '" height="' . $height . '" xmlns="http://www.w3.org/2000/svg">';
        $svg .= '<rect width="100%" height="100%" fill="white"/>';
        
        // Generate simple barcode pattern
        $barWidth = $width / (strlen($code) * 8);
        $x = 10;
        
        for ($i = 0; $i < strlen($code); $i++) {
            $char = ord($code[$i]);
            for ($j = 0; $j < 8; $j++) {
                if (($char >> $j) & 1) {
                    $svg .= '<rect x="' . $x . '" y="10" width="' . $barWidth . '" height="' . ($height - 30) . '" fill="black"/>';
                }
                $x += $barWidth;
            }
        }
        
        // Add text
        $svg .= '<text x="' . ($width / 2) . '" y="' . ($height - 5) . '" text-anchor="middle" font-family="monospace" font-size="12">' . $code . '</text>';
        $svg .= '</svg>';
        
        return $svg;
    }
}