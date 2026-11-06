@props([
    'variant' => 'neutral',
    'size' => 'md',
    'dot' => false,
])

@php
$variantClasses = match($variant) {
    'success' => 'bg-[#2EB872]/10 text-[#2EB872] border border-[#2EB872]/20',
    'error', 'danger' => 'bg-[#E5484D]/10 text-[#E5484D] border border-[#E5484D]/20',
    'warning' => 'bg-[#F59E0B]/10 text-[#D97706] border border-[#F59E0B]/20',
    'info' => 'bg-[#5E63E8]/10 text-[#5E63E8] border border-[#5E63E8]/20',
    'gofood' => 'bg-[#E3F5FC] text-[#0284C7] border border-[#BAE6FD]',
    'shopeefood' => 'bg-[#FDEEE4] text-[#EA580C] border border-[#FED7AA]',
    default => 'bg-[#F0F1F5] text-[#5A5D6C] border border-[#ECEEF2]',
};

$sizeClasses = match($size) {
    'sm' => 'text-[10px] px-2 py-0.5 rounded-md font-semibold tracking-wide',
    default => 'text-xs px-2.5 py-1 rounded-lg font-semibold',
};
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 $variantClasses $sizeClasses whitespace-nowrap select-none"]) }}>
    @if($dot)
        <span class="w-1.5 h-1.5 rounded-full {{ match($variant) {
            'success' => 'bg-[#2EB872]',
            'error', 'danger' => 'bg-[#E5484D]',
            'warning' => 'bg-[#D97706]',
            'info' => 'bg-[#5E63E8]',
            default => 'bg-[#8E92A4]',
        } }}"></span>
    @endif
    <span>{{ $slot }}</span>
</span>

