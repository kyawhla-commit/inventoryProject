<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Purchase Order') }} - {{ $purchase->purchase_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 12px; line-height: 1.5; color: #333; padding: 20px; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 20px; }
        .header h1 { font-size: 24px; margin-bottom: 5px; }
        .header p { color: #666; }
        .info-section { display: flex; justify-content: space-between; margin-bottom: 30px; }
        .info-box { width: 48%; }
        .info-box h3 { font-size: 14px; margin-bottom: 10px; border-bottom: 1px solid #ddd; padding-bottom: 5px; }
        .info-box p { margin-bottom: 5px; }
        .info-box .label { color: #666; display: inline-block; width: 100px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background: #f5f5f5; font-weight: bold; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .total-row { font-weight: bold; background: #f9f9f9; }
        .footer { margin-top: 50px; display: flex; justify-content: space-between; }
        .signature-box { width: 200px; text-align: center; }
        .signature-line { border-top: 1px solid #333; margin-top: 50px; padding-top: 5px; }
        .notes { margin-top: 20px; padding: 15px; background: #f9f9f9; border-radius: 5px; }
        .status-badge { display: inline-block; padding: 5px 15px; border-radius: 20px; font-weight: bold; text-transform: uppercase; font-size: 11px; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-approved { background: #cce5ff; color: #004085; }
        .status-received { background: #d4edda; color: #155724; }
        @media print {
            body { padding: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; cursor: pointer;">
            🖨️ {{ __('Print') }}
        </button>
        <button onclick="window.close()" style="padding: 10px 20px; cursor: pointer; margin-left: 10px;">
            ✕ {{ __('Close') }}
        </button>
    </div>

    <div class="header">
        <h1>{{ __('PURCHASE ORDER') }}</h1>
        <p>{{ config('app.name', 'Inventory Management System') }}</p>
    </div>

    <div class="info-section">
        <div class="info-box">
            <h3>{{ __('Order Information') }}</h3>
            <p><span class="label">{{ __('PO Number:') }}</span> <strong>{{ $purchase->purchase_number }}</strong></p>
            <p><span class="label">{{ __('Date:') }}</span> {{ $purchase->purchase_date->format('F d, Y') }}</p>
            <p><span class="label">{{ __('Status:') }}</span> 
                <span class="status-badge status-{{ $purchase->status }}">
                    {{ \App\Models\Purchase::getStatuses()[$purchase->status] ?? $purchase->status }}
                </span>
            </p>
        </div>
        <div class="info-box">
            <h3>{{ __('Supplier') }}</h3>
            @if($purchase->supplier)
                <p><strong>{{ $purchase->supplier->name }}</strong></p>
                @if($purchase->supplier->address)
                    <p>{{ $purchase->supplier->address }}</p>
                @endif
                @if($purchase->supplier->phone)
                    <p>{{ __('Phone:') }} {{ $purchase->supplier->phone }}</p>
                @endif
                @if($purchase->supplier->email)
                    <p>{{ __('Email:') }} {{ $purchase->supplier->email }}</p>
                @endif
            @else
                <p>-</p>
            @endif
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 5%">#</th>
                <th style="width: 40%">{{ __('Description') }}</th>
                <th style="width: 15%" class="text-center">{{ __('Quantity') }}</th>
                <th style="width: 10%" class="text-center">{{ __('Unit') }}</th>
                <th style="width: 15%" class="text-right">{{ __('Unit Price') }}</th>
                <th style="width: 15%" class="text-right">{{ __('Total') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($purchase->items as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $item->rawMaterial->name ?? $item->product->name ?? '-' }}</td>
                    <td class="text-center">{{ number_format($item->quantity, 2) }}</td>
                    <td class="text-center">{{ $item->rawMaterial->unit ?? $item->unit ?? '-' }}</td>
                    <td class="text-right">{{ number_format($item->unit_price, 2) }}</td>
                    <td class="text-right">{{ number_format($item->total_amount, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="5" class="text-right">{{ __('Grand Total:') }}</td>
                <td class="text-right">{{ number_format($purchase->total_amount, 2) }}</td>
            </tr>
        </tfoot>
    </table>

    @if($purchase->notes)
        <div class="notes">
            <strong>{{ __('Notes:') }}</strong>
            <p>{{ $purchase->notes }}</p>
        </div>
    @endif

    <div class="footer">
        <div class="signature-box">
            <div class="signature-line">{{ __('Prepared By') }}</div>
        </div>
        <div class="signature-box">
            <div class="signature-line">{{ __('Approved By') }}</div>
        </div>
        <div class="signature-box">
            <div class="signature-line">{{ __('Received By') }}</div>
        </div>
    </div>

    <p style="text-align: center; margin-top: 30px; color: #999; font-size: 10px;">
        {{ __('Generated on') }} {{ now()->format('F d, Y H:i:s') }}
    </p>
</body>
</html>
