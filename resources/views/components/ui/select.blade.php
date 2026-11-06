@props([
    'label' => null,
    'error' => null,
    'hint' => null,
    'required' => false,
    'id' => null,
])

@php
$wireModel = null;
foreach ($attributes->getAttributes() as $key => $val) {
    if (str_starts_with($key, 'wire:model')) {
        $wireModel = (string) $val;
        break;
    }
}
$seed = $wireModel ?? $attributes->get('name') ?? $label ?? 'select';
$selectId = $id ?? 'select_' . substr(md5($seed), 0, 10);
@endphp

<div class="flex flex-col gap-1.5 w-full">
    @if($label)
        <label for="{{ $selectId }}" class="flex items-center justify-between text-xs font-semibold text-[#5A5D6C]">
            <span>{{ $label }} @if($required) <span class="text-[#E5484D]">*</span> @endif</span>
            @if($hint)
                <span class="text-[11px] font-normal text-[#8E92A4]">{{ $hint }}</span>
            @endif
        </label>
    @endif

    <div class="relative w-full">
        <select
            id="{{ $selectId }}"
            {{ $attributes->merge([
                'class' => 'w-full bg-white/90 text-[#1E2029] text-xs sm:text-sm rounded-xl py-2.5 pl-3.5 pr-9 ' .
                    ($error ? 'border-[#E5484D] focus:ring-[#E5484D]/20 focus:border-[#E5484D] ' : 'border-[#ECEEF2] focus:ring-[#5E63E8]/20 focus:border-[#5E63E8] ') .
                    'border shadow-2xs focus:bg-white focus:outline-none focus:ring-3 transition-all appearance-none cursor-pointer'
            ]) }}
        >
            {{ $slot }}
        </select>
        <div class="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none text-[#8E92A4]">
            <x-ui.icon name="chevron-down" class="w-4 h-4" />
        </div>
    </div>

    @if($error)
        <p class="text-[11px] font-medium text-[#E5484D] mt-0.5">{{ $error }}</p>
    @endif
</div>

