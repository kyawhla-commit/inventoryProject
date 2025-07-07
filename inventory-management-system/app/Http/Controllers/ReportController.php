<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index()
    {
        return view('reports.index');
    }

    public function salesReport(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $sales = Sale::whereBetween('sale_date', [$startDate, $endDate])->with('items.product')->get();

        $totalSales = $sales->sum('total_amount');

        return view('reports.sales', compact('sales', 'totalSales', 'startDate', 'endDate'));
    }
}
