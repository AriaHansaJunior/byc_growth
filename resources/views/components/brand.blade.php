@props(['compact' => false])

@php
    $logoWidth = $compact ? 85 : 134;
    $logoHeight = $compact ? 38 : 60;
@endphp

<div {{ $attributes->merge(['class' => 'brand' . ($compact ? ' brand-compact' : '')]) }}>
    <img
        src="{{ asset('assets/images/byc-logo.png') }}"
        alt="BYC Growth"
        class="brand-logo"
        width="{{ $logoWidth }}"
        height="{{ $logoHeight }}"
        loading="eager"
        fetchpriority="high"
        decoding="async"
    />
</div>
