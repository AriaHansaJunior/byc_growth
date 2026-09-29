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
        @case('arrow-left')
            <path stroke-linecap="round" stroke-linejoin="round" d="M19 12H5m6 6-6-6 6-6" />
            @break
        @case('users')
            <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.001M15 9a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0ZM6 20.25a6 6 0 0 1 12 0v.75H6v-.75Z" />
            @break
        @case('cash')
            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6H2.25m0 0v8.25m0 0a60.073 60.073 0 0 0 15.797 2.101c.727.198 1.453-.342 1.453-1.096V6.75A.75.75 0 0 0 18.75 6H18a2.25 2.25 0 0 1-2.25-2.25V3.75m-12 0A2.25 2.25 0 0 0 1.5 6v11.25c0 1.242 1.008 2.25 2.25 2.25h16.5" />
            @break
        @case('gamepad')
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.59 14.37a6 6 0 0 1-5.84 7.38v-4.8m5.84-2.58a14.98 14.98 0 0 0 6.16-12.12A2 2 0 0 0 19.59 0H4.41a2 2 0 0 0-2 2.25 14.98 14.98 0 0 0 6.16 12.12m7.02 0v4.8" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 11h4m-2-2v4m9-3h.01m2 2h.01" />
            @break
        @case('sparkles')
            <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456Z" />
            @break
        @case('phone')
            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" />
            @break
        @case('info')
            <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
            @break
        @case('x')
            <path stroke-linecap="round" stroke-linejoin="round" d="m6 6 12 12M18 6 6 18" />
            @break
    @endswitch
</svg>
