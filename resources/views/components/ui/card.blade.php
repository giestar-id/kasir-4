@props([
    'title' => null,
    'subtitle' => null,
    'actions' => null,
    'footer' => null,
    'hoverable' => false,
    'class' => '',
])

<div {{ $attributes->merge([
    'class' => 'glass-card rounded-2xl overflow-hidden flex flex-col ' .
        ($hoverable ? 'glass-card-hover ' : '') .
        $class
]) }}>
    @if($title || $subtitle || $actions || isset($header))
        <div class="px-4 sm:px-5 py-3.5 sm:py-4 border-b border-[#ECEEF2]/60 flex items-center justify-between gap-4">
            @if(isset($header))
                {{ $header }}
            @else
                <div>
                    @if($title)
                        <h3 class="text-sm sm:text-base font-bold text-[#1E2029] tracking-tight">{{ $title }}</h3>
                    @endif
                    @if($subtitle)
                        <p class="text-xs text-[#8E92A4] mt-0.5">{{ $subtitle }}</p>
                    @endif
                </div>
                @if($actions)
                    <div class="flex items-center gap-2">
                        {{ $actions }}
                    </div>
                @endif
            @endif
        </div>
    @endif

    <div class="p-4 sm:p-5 flex-1">
        {{ $slot }}
    </div>

    @if($footer || isset($footerSlot))
        <div class="px-4 sm:px-5 py-3 sm:py-3.5 bg-white/40 border-t border-[#ECEEF2]/60 flex items-center justify-between text-xs text-[#8E92A4]">
            {{ $footer ?? $footerSlot }}
        </div>
    @endif
</div>

