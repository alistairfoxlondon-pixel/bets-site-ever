@php
    /** @var string $name */
    $name = $name ?? 'circle';
    $class = $class ?? 'w-5 h-5';
@endphp

<svg class="{{ $class }}" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
    @switch($name)
        @case('shield')
            <path d="M12 3 5 6v6c0 4.2 2.9 7.6 7 9 4.1-1.4 7-4.8 7-9V6l-7-3Z"/>
            <path d="m9.2 12 2 2 3.6-3.8"/>
            @break
        @case('target')
            <circle cx="12" cy="12" r="8.2"/><circle cx="12" cy="12" r="4.4"/><circle cx="12" cy="12" r="1"/>
            @break
        @case('document')
            <path d="M14 3H7a1.8 1.8 0 0 0-1.8 1.8v14.4A1.8 1.8 0 0 0 7 21h10a1.8 1.8 0 0 0 1.8-1.8V7.8L14 3Z"/>
            <path d="M13.8 3v5h5"/><path d="M8.6 13h6.8M8.6 16.4h4.6"/>
            @break
        @case('search')
            <circle cx="10.8" cy="10.8" r="6.2"/><path d="m15.4 15.4 4 4"/>
            @break
        @case('chart')
            <path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>
            @break
        @case('link')
            <path d="M10.5 13.5a3.8 3.8 0 0 0 5.6.4l2.6-2.6a3.8 3.8 0 0 0-5.4-5.4l-1.5 1.5"/>
            <path d="M13.5 10.5a3.8 3.8 0 0 0-5.6-.4l-2.6 2.6a3.8 3.8 0 0 0 5.4 5.4l1.5-1.5"/>
            @break
        @case('layers')
            <path d="m12 3 8.5 4.6L12 12.2 3.5 7.6 12 3Z"/><path d="m4 12 8 4.4 8-4.4"/><path d="m4 16.4 8 4.4 8-4.4"/>
            @break
        @case('globe')
            <circle cx="12" cy="12" r="8.4"/><path d="M3.6 12h16.8"/><path d="M12 3.6c2.2 2.3 3.4 5.2 3.4 8.4S14.2 18.1 12 20.4c-2.2-2.3-3.4-5.2-3.4-8.4S9.8 5.9 12 3.6Z"/>
            @break
        @case('lock')
            <rect x="4.6" y="10.4" width="14.8" height="9.4" rx="1.8"/><path d="M8.2 10.4V7.8a3.8 3.8 0 0 1 7.6 0v2.6"/>
            @break
        @case('mail')
            <rect x="3" y="5.4" width="18" height="13.2" rx="1.8"/><path d="m3.8 7 8.2 6 8.2-6"/>
            @break
        @case('key')
            <circle cx="8.2" cy="15.8" r="3.6"/><path d="m10.8 13.2 7-7M16 6.4l2.2 2.2M14.4 8l2 2"/>
            @break
        @case('gear')
            <circle cx="12" cy="12" r="3.2"/><path d="M19.4 14.4a1.7 1.7 0 0 0 .3 1.9l.1.1a2 2 0 1 1-2.9 2.9l-.1-.1a1.7 1.7 0 0 0-2.9 1.2v.2a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-3-1.3l-.1.1a2 2 0 1 1-2.9-2.9l.1-.1a1.7 1.7 0 0 0-1.2-2.9h-.2a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.3-3l-.1-.1a2 2 0 1 1 2.9-2.9l.1.1a1.7 1.7 0 0 0 2.9-1.2v-.2a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 3 1.3l.1-.1a2 2 0 1 1 2.9 2.9l-.1.1a1.7 1.7 0 0 0 1.2 2.9h.2a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.6 1Z"/>
            @break
        @case('users')
            <circle cx="9.4" cy="8.4" r="3.4"/><path d="M3.4 19.6a6 6 0 0 1 12 0"/><path d="M16 5.4a3.4 3.4 0 0 1 0 6.6M17.6 14.6a6 6 0 0 1 3 5"/>
            @break
        @case('wallet')
            <path d="M3.6 8.4a2 2 0 0 1 2-2h11.8a2 2 0 0 1 2 2v.8"/><path d="M3.6 8.4v8.2a2 2 0 0 0 2 2h12.8a2 2 0 0 0 2-2v-2.4"/><path d="M20.4 14.2h-3.2a2 2 0 0 1 0-4h3.2z"/>
            @break
        @case('check')
            <path d="m4.8 12.6 4.6 4.6L19.2 7.4"/>
            @break
        @case('cross')
            <path d="M6 6l12 12M18 6 6 18"/>
            @break
        @case('arrow-right')
            <path d="M4.8 12h14.4M13 5.8 19.2 12 13 18.2"/>
            @break
        @case('arrow-down')
            <path d="M12 4.8v14.4M5.8 13 12 19.2 18.2 13"/>
            @break
        @case('menu')
            <path d="M4 7h16M4 12h16M4 17h16"/>
            @break
        @case('plus')
            <path d="M12 5v14M5 12h14"/>
            @break
        @case('download')
            <path d="M12 4v11M7.4 10.6 12 15.2l4.6-4.6M4.6 19.4h14.8"/>
            @break
        @case('copy')
            <rect x="8.4" y="8.4" width="11.2" height="11.2" rx="1.8"/><path d="M15.6 5.6a1.8 1.8 0 0 0-1.8-1.6H6.4a1.8 1.8 0 0 0-1.8 1.8v7.4a1.8 1.8 0 0 0 1.6 1.8"/>
            @break
        @case('external')
            <path d="M14 4.6h5.4V10"/><path d="m19.4 4.6-7.6 7.6"/><path d="M18 14v4.4a1.8 1.8 0 0 1-1.8 1.8H5.6a1.8 1.8 0 0 1-1.8-1.8V7.8A1.8 1.8 0 0 1 5.6 6H10"/>
            @break
        @case('refresh')
            <path d="M20 12a8 8 0 1 1-2.4-5.7"/><path d="M20 4v4.4h-4.4"/>
            @break
        @case('clock')
            <circle cx="12" cy="12" r="8.4"/><path d="M12 7.4V12l3 1.8"/>
            @break
        @case('pen')
            <path d="m4.6 19.4 1-3.6L15.4 6a2 2 0 0 1 2.9 0l.3.3a2 2 0 0 1 0 2.9l-9.8 9.8-3.6 1Z"/><path d="m14.4 7 3.1 3.1"/>
            @break
        @case('chat')
            <path d="M20.4 12.6c0 3.9-3.8 7-8.4 7a9.7 9.7 0 0 1-2.6-.35L4.4 20.6l1.3-3.3a6.6 6.6 0 0 1-2.1-4.7c0-3.9 3.8-7 8.4-7s8.4 3.1 8.4 7Z"/>
            @break
        @case('bell')
            <path d="M18 9.6a6 6 0 1 0-12 0c0 4.4-1.6 6-1.6 6h15.2s-1.6-1.6-1.6-6Z"/><path d="M10 19.2a2.2 2.2 0 0 0 4 0"/>
            @break
        @case('grid')
            <rect x="4" y="4" width="6.6" height="6.6" rx="1"/><rect x="13.4" y="4" width="6.6" height="6.6" rx="1"/><rect x="4" y="13.4" width="6.6" height="6.6" rx="1"/><rect x="13.4" y="13.4" width="6.6" height="6.6" rx="1"/>
            @break
        @case('home')
            <path d="m4 10.4 8-6.4 8 6.4V19a1.8 1.8 0 0 1-1.8 1.8H5.8A1.8 1.8 0 0 1 4 19Z"/><path d="M9.4 20.8v-6.6h5.2v6.6"/>
            @break
        @case('database')
            <ellipse cx="12" cy="6.2" rx="7.4" ry="3"/><path d="M4.6 6.2v11.6c0 1.7 3.3 3 7.4 3s7.4-1.3 7.4-3V6.2"/><path d="M4.6 12c0 1.7 3.3 3 7.4 3s7.4-1.3 7.4-3"/>
            @break
        @case('code')
            <path d="m8.4 8.4-4 3.6 4 3.6M15.6 8.4l4 3.6-4 3.6M13.4 5.4l-2.8 13.2"/>
            @break
        @default
            <circle cx="12" cy="12" r="8.4"/>
    @endswitch
</svg>
