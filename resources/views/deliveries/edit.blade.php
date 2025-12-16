@extends('layouts.app')

@section('title', __('Edit Delivery'))

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="fas fa-edit me-2"></i>{{ __('Edit Delivery') }} {{ $delivery->delivery_number }}</h1>
        <a href="{{ route('deliveries.show', $delivery) }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> {{ __('Back') }}
        </a>
    </div>

    <form action="{{ route('deliveries.update', $delivery) }}" method="POST">
        @csrf
        @method('PUT')
        
        <div class="row">
            <div class="col-md-8">
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
                                       value="{{ old('contact_name', $delivery->contact_name) }}" required>
                                @error('contact_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="contact_phone" class="form-label">{{ __('Contact Phone') }} <span class="text-danger">*</span></label>
                                <input type="text" name="contact_phone" id="contact_phone" 
                                       class="form-control @error('contact_phone') is-invalid @enderror"
                                       value="{{ old('contact_phone', $delivery->contact_phone) }}" required>
                                @error('contact_phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="delivery_address" class="form-label">{{ __('Delivery Address') }} <span class="text-danger">*</span></label>
                            <textarea name="delivery_address" id="delivery_address" rows="2"
                                      class="form-control @error('delivery_address') is-invalid @enderror" required>{{ old('delivery_address', $delivery->delivery_address) }}</textarea>
                            @error('delivery_address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="scheduled_date" class="form-label">{{ __('Scheduled Date') }} <span class="text-danger">*</span></label>
                                <input type="date" name="scheduled_date" id="scheduled_date" 
                                       class="form-control @error('scheduled_date') is-invalid @enderror"
                                       value="{{ old('scheduled_date', $delivery->scheduled_date?->format('Y-m-d')) }}" required>
                                @error('scheduled_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="scheduled_time" class="form-label">{{ __('Scheduled Time') }}</label>
                                <input type="time" name="scheduled_time" id="scheduled_time" 
                                       class="form-control @error('scheduled_time') is-invalid @enderror"
                                       value="{{ old('scheduled_time', $delivery->scheduled_time) }}">
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
                                  placeholder="{{ __('Special instructions or notes...') }}">{{ old('notes', $delivery->notes) }}</textarea>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                {{-- Driver Assignment --}}
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="fas fa-user me-2"></i>{{ __('Driver Assignment') }}</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="driver_name" class="form-label">{{ __('Driver Name') }}</label>
                            <input type="text" name="driver_name" id="driver_name" 
                                   class="form-control" value="{{ old('driver_name', $delivery->driver_name) }}">
                        </div>
                        <div class="mb-3">
                            <label for="driver_phone" class="form-label">{{ __('Driver Phone') }}</label>
                            <input type="text" name="driver_phone" id="driver_phone" 
                                   class="form-control" value="{{ old('driver_phone', $delivery->driver_phone) }}">
                        </div>
                        <div class="mb-3">
                            <label for="vehicle_number" class="form-label">{{ __('Vehicle Number') }}</label>
                            <input type="text" name="vehicle_number" id="vehicle_number" 
                                   class="form-control" value="{{ old('vehicle_number', $delivery->vehicle_number) }}">
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
                                   class="form-control" value="{{ old('delivery_fee', $delivery->delivery_fee) }}" 
                                   min="0" step="100">
                        </div>
                    </div>
                </div>

                {{-- Submit --}}
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-save me-2"></i>{{ __('Update Delivery') }}
                    </button>
                    <a href="{{ route('deliveries.show', $delivery) }}" class="btn btn-outline-secondary">
                        {{ __('Cancel') }}
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
