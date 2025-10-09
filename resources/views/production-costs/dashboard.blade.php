@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <h2 class="mb-4">Production Cost Dashboard</h2>

    <!-- Cost Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Material Costs</h5>
                    <div class="row">
                        <div class="col">
                            <p class="mb-1">Planned: ${{ number_format($costSummary->total_planned_material, 2) }}</p>
                            <p class="mb-1">Actual: ${{ number_format($costSummary->total_actual_material, 2) }}</p>
                            <p class="mb-0 {{ $costSummary->total_actual_material - $costSummary->total_planned_material > 0 ? 'text-danger' : 'text-success' }}">
                                Variance: ${{ number_format($costSummary->total_actual_material - $costSummary->total_planned_material, 2) }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Labor Costs</h5>
                    <div class="row">
                        <div class="col">
                            <p class="mb-1">Planned: ${{ number_format($costSummary->total_planned_labor, 2) }}</p>
                            <p class="mb-1">Actual: ${{ number_format($costSummary->total_actual_labor, 2) }}</p>
                            <p class="mb-0 {{ $costSummary->total_actual_labor - $costSummary->total_planned_labor > 0 ? 'text-danger' : 'text-success' }}">
                                Variance: ${{ number_format($costSummary->total_actual_labor - $costSummary->total_planned_labor, 2) }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Overhead Costs</h5>
                    <div class="row">
                        <div class="col">
                            <p class="mb-1">Planned: ${{ number_format($costSummary->total_planned_overhead, 2) }}</p>
                            <p class="mb-1">Actual: ${{ number_format($costSummary->total_actual_overhead, 2) }}</p>
                            <p class="mb-0 {{ $costSummary->total_actual_overhead - $costSummary->total_planned_overhead > 0 ? 'text-danger' : 'text-success' }}">
                                Variance: ${{ number_format($costSummary->total_actual_overhead - $costSummary->total_planned_overhead, 2) }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Variance Analysis -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Top Variances</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Production Plan</th>
                                    <th>Material Variance</th>
                                    <th>Labor Variance</th>
                                    <th>Overhead Variance</th>
                                    <th>Total Variance</th>
                                    <th>Primary Reason</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($varianceAnalysis as $cost)
                                <tr>
                                    <td>{{ $cost->productionPlan->name }}</td>
                                    <td class="{{ $cost->material_variance > 0 ? 'text-danger' : 'text-success' }}">
                                        ${{ number_format($cost->material_variance, 2) }}
                                    </td>
                                    <td class="{{ $cost->labor_variance > 0 ? 'text-danger' : 'text-success' }}">
                                        ${{ number_format($cost->labor_variance, 2) }}
                                    </td>
                                    <td class="{{ $cost->overhead_variance > 0 ? 'text-danger' : 'text-success' }}">
                                        ${{ number_format($cost->overhead_variance, 2) }}
                                    </td>
                                    <td class="{{ $cost->total_variance > 0 ? 'text-danger' : 'text-success' }}">
                                        ${{ number_format($cost->total_variance, 2) }}
                                    </td>
                                    <td>
                                        {{ $cost->varianceReasons->first()?->reason ?? 'N/A' }}
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Monthly Trend Chart -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Monthly Cost Trend</h5>
                </div>
                <div class="card-body">
                    <canvas id="monthlyTrendChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('monthlyTrendChart').getContext('2d');
    const monthlyData = @json($monthlyTrend);
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: monthlyData.map(item => item.month),
            datasets: [
                {
                    label: 'Average Variance',
                    data: monthlyData.map(item => item.avg_variance),
                    borderColor: 'rgb(255, 99, 132)',
                    tension: 0.1
                },
                {
                    label: 'Total Cost',
                    data: monthlyData.map(item => item.total_cost),
                    borderColor: 'rgb(54, 162, 235)',
                    tension: 0.1
                }
            ]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
});
</script>
@endpush
@endsection