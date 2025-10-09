@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Production Cost Details - {{ $productionPlan->name }}</h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('production-costs.update-actual', $productionPlan) }}" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Actual Material Cost</label>
                                    <input type="number" step="0.01" name="actual_material_cost" 
                                           class="form-control" value="{{ $productionCost->actual_material_cost ?? 0 }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Actual Labor Cost</label>
                                    <input type="number" step="0.01" name="actual_labor_cost" 
                                           class="form-control" value="{{ $productionCost->actual_labor_cost ?? 0 }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Actual Overhead Cost</label>
                                    <input type="number" step="0.01" name="actual_overhead_cost" 
                                           class="form-control" value="{{ $productionCost->actual_overhead_cost ?? 0 }}">
                                </div>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-12">
                                <h5>Variance Reasons</h5>
                                <div id="variance-reasons">
                                    <div class="row mb-2">
                                        <div class="col-md-3">
                                            <select name="variance_reasons[0][type]" class="form-control">
                                                <option value="material">Material</option>
                                                <option value="labor">Labor</option>
                                                <option value="overhead">Overhead</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <input type="number" step="0.01" name="variance_reasons[0][amount]" 
                                                   class="form-control" placeholder="Variance Amount">
                                        </div>
                                        <div class="col-md-6">
                                            <input type="text" name="variance_reasons[0][description]" 
                                                   class="form-control" placeholder="Reason for variance">
                                        </div>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-sm btn-secondary" onclick="addVarianceReason()">
                                    Add Another Reason
                                </button>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">Update Costs</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
let reasonCount = 1;

function addVarianceReason() {
    const html = `
        <div class="row mb-2">
            <div class="col-md-3">
                <select name="variance_reasons[${reasonCount}][type]" class="form-control">
                    <option value="material">Material</option>
                    <option value="labor">Labor</option>
                    <option value="overhead">Overhead</option>
                </select>
            </div>
            <div class="col-md-3">
                <input type="number" step="0.01" name="variance_reasons[${reasonCount}][amount]" 
                       class="form-control" placeholder="Variance Amount">
            </div>
            <div class="col-md-6">
                <input type="text" name="variance_reasons[${reasonCount}][description]" 
                       class="form-control" placeholder="Reason for variance">
            </div>
        </div>
    `;
    document.getElementById('variance-reasons').insertAdjacentHTML('beforeend', html);
    reasonCount++;
}
</script>
@endpush
@endsection