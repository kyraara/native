@props(['name'])
<svg {{ $attributes->class(['nc-icon', 'nc-public-icon']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
@switch($name)
    @case('brief') <path d="M5 4h10l4 4v13H5Z" fill="currentColor" fill-opacity=".10"/><path d="M15 4v5h4M8 12h8M8 16h5M9 2v4m3-4v4"/> @break
    @case('research') <path d="M3 4h13v16H3Z" fill="currentColor" fill-opacity=".10"/><path d="M6 8h7M6 11h4M6 15h3"/><circle cx="16" cy="15" r="4"/><path d="m19 18 3 3"/> @break
    @case('report') <path d="M5 3h10l4 4v14H5Z" fill="currentColor" fill-opacity=".10"/><path d="M15 3v5h4M8 17v-3m4 3v-7m4 7v-5"/> @break
    @case('slides') <rect x="3" y="4" width="18" height="13" rx="2" fill="currentColor" fill-opacity=".10"/><path d="M12 17v4m-4 0h8M7 12l3-3 3 2 4-4M1 4h22"/> @break
    @case('code') <rect x="2" y="4" width="20" height="16" rx="2" fill="currentColor" fill-opacity=".10"/><path d="M2 8h20m-15 3-3 3 3 3m10-6 3 3-3 3m-4-6-2 6"/> @break
    @case('services') <rect x="3" y="3" width="7" height="7" rx="2" fill="currentColor" fill-opacity=".10"/><rect x="14" y="3" width="7" height="7" rx="2"/><rect x="3" y="14" width="7" height="7" rx="2"/><path d="M17.5 14v7M14 17.5h7"/> @break
    @case('handshake') <path d="m2 8 5-4 5 2 5-2 5 4-3 8-6 4-6-3-5-9Z" fill="currentColor" fill-opacity=".10"/><path d="m7 4 5 2-4 5 2 2 4-3 5 6M7 17l2-3m1 5 2-3m1 4 2-3M2 8l5 2m15-2-5 2"/> @break
    @case('progress') <path d="M4 4h12v16H4Z" fill="currentColor" fill-opacity=".10"/><path d="M7 8h5M7 12h2M7 16h2"/><circle cx="17" cy="16" r="5"/><path d="M17 13v3l2 1"/> @break
    @case('delivery') <path d="m3 7 9-4 9 4v12l-9 3-9-3Z" fill="currentColor" fill-opacity=".10"/><path d="m3 7 9 4 9-4M12 11v11m-3-6 2 2 4-4"/> @break
    @case('shield') <path d="m12 2 8 4v6c0 5-5 8-8 10-3-2-8-5-8-10V6Z" fill="currentColor" fill-opacity=".10"/><path d="m8 12 3 3 5-6"/> @break
    @case('revision') <path d="M20 8a8 8 0 0 0-14-3L3 8m0-5v5h5m-4 8a8 8 0 0 0 14 3l3-3m0 5v-5h-5"/><path d="m8 13 3 3 5-7"/> @break
    @case('chat') <path d="M4 3h16a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2h-9l-6 4v-4H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z" fill="currentColor" fill-opacity=".10"/><path d="M7 8h10M7 12h7"/> @break
    @case('assistant') <path d="M5 7h14a2 2 0 0 1 2 2v10H3V9a2 2 0 0 1 2-2Z" fill="currentColor" fill-opacity=".10"/><path d="M12 3v4M1 12h2m18 0h2M9 15h6"/><circle cx="8" cy="11" r=".7" fill="currentColor"/><circle cx="16" cy="11" r=".7" fill="currentColor"/><circle cx="12" cy="2" r="1"/> @break
    @case('headset') <path d="M4 13V10a8 8 0 0 1 16 0v7a4 4 0 0 1-4 4h-3"/><rect x="2" y="10" width="5" height="8" rx="2" fill="currentColor" fill-opacity=".10"/><rect x="17" y="10" width="5" height="8" rx="2" fill="currentColor" fill-opacity=".10"/> @break
    @case('send') <path d="m3 3 19 9-19 9 3-9Z" fill="currentColor" fill-opacity=".10"/><path d="M6 12h16"/> @break
    @case('phone-lock') <rect x="4" y="2" width="12" height="20" rx="2" fill="currentColor" fill-opacity=".10"/><path d="M8 5h4m-3 14h2"/><rect x="12" y="11" width="10" height="8" rx="2"/><path d="M14 11V8a3 3 0 0 1 6 0v3m-3 4v1"/> @break
    @case('mail') <rect x="2" y="4" width="20" height="16" rx="3" fill="currentColor" fill-opacity=".10"/><path d="m3 6 9 7 9-7"/> @break
    @case('pin') <path d="M19 9c0 6-7 13-7 13S5 15 5 9a7 7 0 0 1 14 0Z" fill="currentColor" fill-opacity=".10"/><circle cx="12" cy="9" r="2.5"/> @break
    @case('bolt') <path d="m14 2-9 12h6l-1 8L21 9h-7Z" fill="currentColor" fill-opacity=".10"/> @break
@endswitch
</svg>
