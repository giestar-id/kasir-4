@props([
    'label' => null,
    'icon' => null,
    'prefix' => null,
    'suffix' => null,
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
$seed = $wireModel ?? $attributes->get('name') ?? $label ?? 'input';
$inputId = $id ?? 'input_' . substr(md5($seed), 0, 10);
@endphp

<div class="flex flex-col gap-1.5 w-full">
    @if($label)
        <label for="{{ $inputId }}" class="flex items-center justify-between text-xs font-semibold text-[#5A5D6C]">
            <span>{{ $label }} @if($required) <span class="text-[#E5484D]">*</span> @endif</span>
            @if($hint)
                <span class="text-[11px] font-normal text-[#8E92A4]">{{ $hint }}</span>
            @endif
        </label>
    @endif

    <div class="relative flex items-center w-full">
        @if($icon)
            <div class="absolute left-3 text-[#8E92A4] pointer-events-none flex items-center justify-center">
                <x-ui.icon :name="$icon" class="w-4 h-4" />
            </div>
        @elseif($prefix)
            <div class="absolute left-3 text-xs font-bold text-[#8E92A4] pointer-events-none">
                {{ $prefix }}
            </div>
        @endif

        <input
            id="{{ $inputId }}"
            {{ $attributes->merge([
                'class' => 'w-full bg-white/90 text-[#1E2029] text-xs sm:text-sm rounded-xl py-2.5 ' .
                    ($icon ? 'pl-9 ' : ($prefix ? 'pl-9 ' : 'pl-3.5 ')) .
                    ($suffix ? 'pr-9 ' : 'pr-3.5 ') .
                    ($error ? 'border-[#E5484D] focus:ring-[#E5484D]/20 focus:border-[#E5484D] ' : 'border-[#ECEEF2] focus:ring-[#5E63E8]/20 focus:border-[#5E63E8] ') .
                    'border shadow-2xs placeholder:text-[#8E92A4]/60 focus:bg-white focus:outline-none focus:ring-3 transition-all'
            ]) }}
        />

        @if($suffix)
            <div class="absolute right-3 text-xs font-semibold text-[#8E92A4] pointer-events-none">
                {{ $suffix }}
            </div>
        @endif
    </div>

    @if($error)
        <p class="text-[11px] font-medium text-[#E5484D] mt-0.5">{{ $error }}</p>
    @endif
</div>

