@extends('layouts.app')

@section('title', __('Create Delivery'))

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="fas fa-truck me-2"></i>{{ __('Create Delivery') }}</h1>
        <a href="{{ route('deliveries.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> {{ __('Back') }}
        </a>
    </div>

    <form action="{{ route('deliveries.store') }}" method="POST">
        @csrf
        
        <div class="row">
            <div class="col-md-8">
                {{-- Order Selection --}}
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="fas fa-shopping-cart me-2"></i>{{ __('Select Order') }}</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="order_id" class="form-label">{{ __('Order') }} <span class="text-danger">*</span></label>
                            <select name="order_id" id="order_id" class="form-select @error('order_id') is-invalid @enderror" required>
                                <option value="">{{ __('Select an order') }}</option>
                                @foreach($orders as $o)
                                    <option value="{{ $o->id }}" 
                                            data-customer="{{ $o->customer->name ?? '' }}"
                                            data-phone="{{ $o->customer->phone ?? '' }}"
                                            data-address="{{ $o->customer->address ?? '' }}"
                                            {{ (old('order_id', $order?->id) == $o->id) ? 'selected' : '' }}>
                                        #{{ $o->id }} - {{ $o->customer->name ?? 'N/A' }} 
                                        ({{ $o->order_date->format('M d') }} - Ks {{ number_format($o->total_amount, 0) }})
                                    </option>
                                @endforeach
                            </select>
                            @error('order_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Delivery Details --}}
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="fas fa-map-marker-alt me-2"></i>{{ __('Delivery Details') }}</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="contact_name" class="form-label">{{ __('Contact Name') }} <span class="text-danger">*</span></label>
                                <input type="text" name="contact_name" id="contact_name" 
                                       class="form-control @error('contact_name') is-invalid @enderror"
                                       value="{{ old('contact_name', $order?->customer?->name) }}" required>
                                @error('contact_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="contact_phone" class="form-label">{{ __('Contact Phone') }} <span class="text-danger">*</span></label>
                                <input type="text" name="contact_phone" id="contact_phone" 
                                       class="form-control @error('contact_phone') is-invalid @enderror"
                                       value="{{ old('contact_phone', $order?->customer?->phone) }}" required>
                                @error('contact_phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="delivery_address" class="form-label">{{ __('Delivery Address') }} <span class="text-danger">*</span></label>
                            <textarea name="delivery_address" id="delivery_address" rows="2"
                                      class="form-control @error('delivery_address') is-invalid @enderror" required>{{ old('delivery_address', $order?->customer?->address) }}</textarea>
                            @error('delivery_address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="scheduled_date" class="form-label">{{ __('Scheduled Date') }} <span class="text-danger">*</span></label>
                                <input type="date" name="scheduled_date" id="scheduled_date" 
                                       class="form-control @error('scheduled_date') is-invalid @enderror"
                                       value="{{ old('scheduled_date', now()->format('Y-m-d')) }}" required>
                                @error('scheduled_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="scheduled_time" class="form-label">{{ __('Scheduled Time') }}</label>
                                <input type="time" name="scheduled_time" id="scheduled_time" 
                                       class="form-control @error('scheduled_time') is-invalid @enderror"
                                       value="{{ old('scheduled_time') }}">
                                @error('scheduled_time')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Notes --}}
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="fas fa-sticky-note me-2"></i>{{ __('Notes') }}</h5>
                    </div>
                    <div class="card-body">
                        <textarea name="notes" id="notes" rows="3" class="form-control"
                                  placeholder="{{ __('Special instructions or notes...') }}">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                {{-- Driver Assignment (Optional) --}}
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="fas fa-user me-2"></i>{{ __('Driver Assignment') }}</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small">{{ __('Optional: Assign a driver now or later') }}</p>
                        <div class="mb-3">
                            <label for="driver_name" class="form-label">{{ __('Driver Name') }}</label>
                            <input type="text" name="driver_name" id="driver_name" 
                                   class="form-control" value="{{ old('driver_name') }}">
                        </div>
                        <div class="mb-3">
                            <label for="driver_phone" class="form-label">{{ __('Driver Phone') }}</label>
                            <input type="text" name="driver_phone" id="driver_phone" 
                                   class="form-control" value="{{ old('driver_phone') }}">
                        </div>
                        <div class="mb-3">
                            <label for="vehicle_number" class="form-label">{{ __('Vehicle Number') }}</label>
                            <input type="text" name="vehicle_number" id="vehicle_number" 
                                   class="form-control" value="{{ old('vehicle_number') }}">
                        </div>
                    </div>
                </div>

                {{-- Delivery Fee --}}
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="fas fa-money-bill me-2"></i>{{ __('Delivery Fee') }}</h5>
                    </div>
                    <div class="card-body">
                        <div class="input-group">
                            <span class="input-group-text">Ks</span>
                            <input type="number" name="delivery_fee" id="delivery_fee" 
                                   class="form-control" value="{{ old('delivery_fee', 0) }}" 
                                   min="0" step="100">
                        </div>
                    </div>
                </div>

                {{-- Submit --}}
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-save me-2"></i>{{ __('Create Delivery') }}
                    </button>
                    <a href="{{ route('deliveries.index') }}" class="btn btn-outline-secondary">
                        {{ __('Cancel') }}
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
document.getElementById('order_id').addEventListener('change', function() {
    const selected = this.options[this.selectedIndex];
    if (selected.value) {
        document.getElementById('contact_name').value = selected.dataset.customer || '';
        document.getElementById('contact_phone').value = selected.dataset.phone || '';
        document.getElementById('delivery_address').value = selected.dataset.address || '';
    }
});
</script>
@endpush
@endsection
