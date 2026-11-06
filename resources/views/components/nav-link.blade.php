@props([
    'route',
    'icon' => 'circle',
    'label' => '',
])

@php
$isActive = request()->routeIs($route) || request()->routeIs($route . '.*');
$classes = $isActive
    ? 'bg-[#5E63E8] text-white font-bold shadow-md shadow-[#5E63E8]/30 border border-white/25'
    : 'text-[#5A5D6C] hover:text-[#1E2029] hover:bg-white/80 hover:shadow-2xs font-semibold';
@endphp

<a
    href="{{ route($route) }}"
    wire:navigate.hover
    @click="mobileNavOpen = false"
    :title="(isDesktop && desktopCollapsed) ? '{{ $label }}' : ''"
    class="flex items-center gap-2.5 rounded-xl text-xs sm:text-sm transition-all duration-200 group min-w-0 {{ $classes }}"
    :class="(isDesktop && desktopCollapsed) ? 'justify-center px-0 w-10 h-10 mx-auto' : 'px-3.5 py-2.5 w-full'"
>
    <x-ui.icon
        :name="$icon"
        class="w-4 h-4 shrink-0 transition-transform duration-200 group-hover:scale-110 {{ $isActive ? 'text-white' : 'text-[#8E92A4] group-hover:text-[#5E63E8]' }}"
    />
    <span
        x-show="!(isDesktop && desktopCollapsed)"
        x-cloak
        class="truncate whitespace-nowrap min-w-0 flex-1 text-left"
    >
        {{ $label }}
    </span>
</a>
