@props([
    'type' => 'toggle',
    'size' => 'md',
    'showText' => false,
    'showIcon' => true,
])

@php
$sizes = [
    'sm' => 'btn-sm',
    'md' => '',
    'lg' => 'btn-lg'
];
@endphp

@if($type === 'toggle')
    <button 
        data-theme-toggle="toggle"
        class="theme-toggle {{ $sizes[$size] }}"
        aria-label="Toggle theme"
        type="button"
        {{ $attributes }}
    >
        @if($showIcon)
            <i class="theme-icon fas fa-adjust"></i>
        @endif
        @if($showText)
            <span class="theme-text ms-1">Theme</span>
        @endif
    </button>
@elseif($type === 'select')
    <div class="dropdown">
        <button 
            class="theme-toggle dropdown-toggle {{ $sizes[$size] }}" 
            type="button" 
            data-bs-toggle="dropdown"
            aria-expanded="false"
            aria-label="Select theme"
        >
            @if($showIcon)
                <i class="theme-icon fas fa-adjust"></i>
            @endif
            @if($showText)
                <span class="theme-text ms-1">Theme</span>
            @endif
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
            <li>
                <button class="dropdown-item d-flex align-items-center" data-theme-option="light">
                    <i class="fas fa-sun me-2 text-warning"></i>
                    <span>{{ __('Light') }}</span>
                    <i class="fas fa-check ms-auto theme-check" data-theme-check="light" style="visibility: hidden;"></i>
                </button>
            </li>
            <li>
                <button class="dropdown-item d-flex align-items-center" data-theme-option="dark">
                    <i class="fas fa-moon me-2 text-primary"></i>
                    <span>{{ __('Dark') }}</span>
                    <i class="fas fa-check ms-auto theme-check" data-theme-check="dark" style="visibility: hidden;"></i>
                </button>
            </li>
            <li><hr class="dropdown-divider"></li>
            <li>
                <button class="dropdown-item d-flex align-items-center" data-theme-option="auto">
                    <i class="fas fa-adjust me-2 text-secondary"></i>
                    <span>{{ __('Auto (System)') }}</span>
                    <i class="fas fa-check ms-auto theme-check" data-theme-check="auto" style="visibility: hidden;"></i>
                </button>
            </li>
        </ul>
    </div>
@endif
