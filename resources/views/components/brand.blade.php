@props(['compact' => false])

<div {{ $attributes->merge(['class' => 'brand' . ($compact ? ' brand-compact' : '')]) }}>
    <img src="{{ asset('assets/images/byc-logo.png') }}" alt="BYC Growth" class="brand-logo" />
</div>

