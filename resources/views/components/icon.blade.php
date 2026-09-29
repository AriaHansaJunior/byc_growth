@props(['name' => 'arrow', 'class' => 'icon'])

<svg aria-hidden="true" class="{{ $class }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
    @switch($name)
        @case('arrow')
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M13 6l6 6-6 6" />
            @break
        @case('book')
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 5.5A2.5 2.5 0 0 1 6.5 3H11v16H6.5A2.5 2.5 0 0 0 4 21V5.5Z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M20 5.5A2.5 2.5 0 0 0 17.5 3H13v16h4.5A2.5 2.5 0 0 1 20 21V5.5Z" />
            @break
        @case('edit')
            <path stroke-linecap="round" stroke-linejoin="round" d="m4 20 4.5-1 10-10-3.5-3.5-10 10L4 20Z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="m13.5 7 3.5 3.5" />
            @break
        @case('home')
            <path stroke-linecap="round" stroke-linejoin="round" d="m3 11 9-8 9 8" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 10v11h14V10M9 21v-7h6v7" />
            @break
        @case('trophy')
            <path stroke-linecap="round" stroke-linejoin="round" d="M8 4h8v5a4 4 0 0 1-8 0V4Z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M8 6H4v2a4 4 0 0 0 4 4M16 6h4v2a4 4 0 0 1-4 4M12 13v4M8 21h8M9 17h6v4" />
            @break
        @case('x')
            <path stroke-linecap="round" stroke-linejoin="round" d="m6 6 12 12M18 6 6 18" />
            @break
    @endswitch
</svg>
