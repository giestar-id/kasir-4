@props([
    'variant' => 'primary',
    'size' => 'md',
    'type' => 'button',
    'icon' => null,
    'disabled' => false,
])

@php
$baseClasses = 'inline-flex items-center justify-center font-medium transition-all duration-150 focus:outline-none focus:ring-2 focus:ring-offset-1 select-none disabled:opacity-50 disabled:cursor-not-allowed disabled:pointer-events-none cursor-pointer';

$sizeClasses = match($size) {
    'sm' => 'text-xs px-3 py-1.5 rounded-lg gap-1.5 h-8',
    'lg' => 'text-sm sm:text-base px-6 py-3 rounded-xl gap-2.5 h-12 font-bold shadow-md',
    default => 'text-xs sm:text-sm px-4 py-2.5 rounded-xl gap-2 h-10 font-semibold shadow-sm',
};

$variantClasses = match($variant) {
    'primary' => 'bg-[#5E63E8] hover:bg-[#4D52D6] active:bg-[#4448CE] text-white focus:ring-[#5E63E8]/30 shadow-[#5E63E8]/20',
    'secondary' => 'bg-white/90 hover:bg-white text-[#1E2029] border border-[#ECEEF2] hover:border-[#D9DCE3] active:bg-[#F3F4F7] focus:ring-[#5E63E8]/20 shadow-xs',
    'danger' => 'bg-[#E5484D] hover:bg-[#D03B40] active:bg-[#BA2E33] text-white focus:ring-[#E5484D]/30 shadow-[#E5484D]/20',
    'outline' => 'bg-transparent border border-[#ECEEF2] hover:bg-white text-[#5A5D6C] hover:text-[#1E2029] focus:ring-[#5E63E8]/20',
    'ghost' => 'bg-transparent hover:bg-black/5 text-[#5A5D6C] hover:text-[#1E2029] focus:ring-black/10',
    default => 'bg-[#5E63E8] text-white',
};
@endphp

<button
    type="{{ $type }}"
    {{ $disabled ? 'disabled' : '' }}
    {{ $attributes->merge(['class' => "$baseClasses $sizeClasses $variantClasses"]) }}
>
    @if($icon)
        <x-ui.icon :name="$icon" class="{{ $size === 'sm' ? 'w-3.5 h-3.5' : 'w-4 h-4' }}" />
    @endif
    <span>{{ $slot }}</span>
</button>

