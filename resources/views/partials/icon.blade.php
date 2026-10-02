{{-- 
    Partial icon SVG solid/filled style, currentColor biar ngikut warna teks.
    Pakai: @include('partials.icon', ['name' => 'server', 'size' => 20])
--}}
@php
    $size = $size ?? 20;
@endphp

@switch($name)
    @case('logo')
        <svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="currentColor" style="vertical-align:-4px;">
            <path d="M3 4a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4Zm4 3a1 1 0 1 0 0-2 1 1 0 0 0 0 2ZM3 16a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4Zm4 3a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z"/>
        </svg>
        @break

    @case('server')
        <svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="currentColor">
            <path d="M3 4a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4Zm4 3a1 1 0 1 0 0-2 1 1 0 0 0 0 2ZM3 16a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4Zm4 3a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z"/>
        </svg>
        @break

    @case('globe')
        <svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="currentColor">
            <path fill-rule="evenodd" clip-rule="evenodd" d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20Zm5.94 8H15.9a15.6 15.6 0 0 0-1.2-5.3A8.02 8.02 0 0 1 17.94 10ZM12 4.06c.83 1.2 1.55 3.02 1.87 5.94h-3.74c.32-2.92 1.04-4.74 1.87-5.94ZM9.3 4.7A15.6 15.6 0 0 0 8.1 10H6.06A8.02 8.02 0 0 1 9.3 4.7ZM6.06 14H8.1c.16 1.98.58 3.8 1.2 5.3A8.02 8.02 0 0 1 6.06 14Zm3.94 0h3.74c-.32 2.92-1.04 4.74-1.87 5.94-.83-1.2-1.55-3.02-1.87-5.94Zm5.7 5.3c.62-1.5 1.04-3.32 1.2-5.3h2.04a8.02 8.02 0 0 1-3.24 5.3Z"/>
        </svg>
        @break

    @case('egg')
        <svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="currentColor">
            <path d="M12 2C8.5 2 5 8.5 5 14a7 7 0 0 0 14 0c0-5.5-3.5-12-7-12Z"/>
        </svg>
        @break

    @case('package')
        <svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="currentColor">
            <path d="M11.5 2.3a1 1 0 0 1 1 0l7 4a1 1 0 0 1 .5.87v9.66a1 1 0 0 1-.5.87l-7 4a1 1 0 0 1-1 0l-7-4a1 1 0 0 1-.5-.87V7.17a1 1 0 0 1 .5-.87l7-4Zm.5 1.98L6.1 7.17 12 10.58l5.9-3.4L12 4.28ZM5 8.9v7.55l6 3.43v-7.55L5 8.9Zm14 0-6 3.43v7.55l6-3.43V8.9Z"/>
        </svg>
        @break

    @case('plug')
        <svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="currentColor">
            <path d="M8 2a1 1 0 0 1 1 1v3h6V3a1 1 0 1 1 2 0v3h1a1 1 0 0 1 1 1v2a6 6 0 0 1-5 5.92V19a3 3 0 0 1-3 3h-0a3 3 0 0 1-3-3v-4.08A6 6 0 0 1 3 9V7a1 1 0 0 1 1-1h1V3a1 1 0 0 1 1-1Z"/>
        </svg>
        @break

    @case('sparkle')
        <svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="currentColor">
            <path d="M12 2c.4 3.6 2 5.6 6 6-4 .4-5.6 2.4-6 6-.4-3.6-2-5.6-6-6 4-.4 5.6-2.4 6-6Z"/>
            <path d="M19 15c.2 1.6.9 2.4 2.5 2.7-1.6.3-2.3 1.1-2.5 2.7-.2-1.6-.9-2.4-2.5-2.7 1.6-.3 2.3-1.1 2.5-2.7Z"/>
        </svg>
        @break
    @case('home')
        <svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-4px;">
            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>
        </svg>
        @break

    @case('settings')
        <svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-4px;">
            <line x1="4" y1="21" x2="4" y2="14"/><line x1="4" y1="10" x2="4" y2="3"/><line x1="12" y1="21" x2="12" y2="12"/><line x1="12" y1="8" x2="12" y2="3"/><line x1="20" y1="21" x2="20" y2="16"/><line x1="20" y1="12" x2="20" y2="3"/><line x1="1" y1="14" x2="7" y2="14"/><line x1="9" y1="8" x2="15" y2="8"/><line x1="17" y1="16" x2="23" y2="16"/>
        </svg>
        @break

    @case('api')
        <svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-4px;">
            <polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/>
        </svg>
        @break

    @case('database')
        <svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-4px;">
            <ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/>
        </svg>
        @break

    @case('location')
        <svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-4px;">
            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>
        </svg>
        @break

    @case('mounts')
        <svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-4px;">
            <line x1="22" y1="12" x2="2" y2="12"/><path d="M5.45 5.11L2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/><line x1="6" y1="16" x2="6.01" y2="16"/><line x1="10" y1="16" x2="10.01" y2="16"/>
        </svg>
        @break

    @case('users')
        <svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-4px;">
            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
        </svg>
        @break

    @case('user')
        <svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-4px;">
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
        </svg>
        @break

    @case('menu')
        <svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-4px;">
            <line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/>
        </svg>
        @break

    @case('logout')
        <svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-4px;">
            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>
        </svg>
        @break

@endswitch
